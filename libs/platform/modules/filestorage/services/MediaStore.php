<?php

namespace app\modules\filestorage\services;

use Yii;
use yii\db\Query;

/**
 * Stores uploaded images/PDFs and builds the public URL that the apps save in the existing image columns
 * (profile_image, aadhar_card_front, ...). Replaces Firebase Storage / ImageKit.
 *
 * Storage driver (MEDIA_DRIVER env var or params['media']['driver']):
 *   disk (default) - files under the media storage path, metadata row in `media`. Keeps the database small
 *                    and is not limited by max_allowed_packet.
 *   db             - bytes in the `media`.`data` BLOB column.
 *   s3             - an S3-compatible object storage (Cloudflare R2, Backblaze B2, DigitalOcean Spaces, AWS S3, MinIO ...), see
 *                    S3Client. The server keeps only a small row per file; the bytes live in the bucket and the apps read them
 *                    through a short redirect (the bytes never pass through PHP). Set media_driver=s3 in the dashboard.
 *
 * Identity documents (driving licence, Aadhaar, PAN card) and the profile photo are kept for the partner's whole life and are never deleted
 * by anything in this system (see isLifelong, remove, media/prune-rides, media/purge-orphans). They are stored apart from the temporary
 * ride photos: in their own bucket (setting media_s3_bucket_documents) and in the folder profile/<partner id>/<document type>/ (driving_license, aadhar,
 * pan_card/, profile_image/). On the provider, put a bucket lock rule with indefinite retention on that bucket (see DEPLOY.md section 20).
 *
 * Each file has a kind that decides how much it is shrunk and how long it is kept:
 *   document (default)  Aadhaar, PAN, licence, bank proof ...: 1600 px, quality 82, kept, private link
 *   profile             profile photo: 800 px, quality 80, kept
 *   ride                selfie and car photos of a ride: 1280 px, quality 72, deleted after MEDIA_RIDE_RETENTION_DAYS (php yii media/prune)
 *
 * Other settings (env var / params['media'][key]):
 *   MEDIA_STORAGE_PATH / path          directory for the disk driver (default @runtime/media)
 *   maxUploadBytes                     largest accepted upload (default 10 MB)
 *   maxDimension                       longest image side kept, larger images are downscaled (default 1600)
 *   jpegQuality                        re-encode quality (default 82)
 *
 * Images are re-encoded on upload: EXIF orientation is applied, metadata (including GPS) is stripped,
 * large photos are downscaled and identical files from the same owner are stored once.
 */
class MediaStore
{
    const DRIVER_DISK = 'disk';
    const DRIVER_DB = 'db';
    const DRIVER_S3 = 's3';

    const KIND_DOCUMENT = 'document';
    const KIND_PROFILE = 'profile';
    const KIND_RIDE = 'ride';

    /** the identity documents a partner uploads, each kept in its own folder */
    const DOC_TYPES = ['driving_license', 'aadhar', 'pan_card', 'profile_image', 'other'];

    const DEFAULT_MAX_UPLOAD = 10485760; // 10 MB
    const DEFAULT_MAX_DIMENSION = 1600;
    const DEFAULT_JPEG_QUALITY = 82;
    const MAX_PIXELS = 25000000;

    private static $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'application/pdf' => 'pdf',
    ];

    // [doc:start] saveFile - Save File
    /**
     * @param string      $path         readable file (e.g. an uploaded temp file)
     * @param int|null    $ownerUserId
     * @param string|null $originalName
     * @return array [id, url, mime, size]
     * @throws \InvalidArgumentException when the file is missing, too large or not an allowed type
     */
    public static function saveFile($path, $ownerUserId = null, $originalName = null, $kind = self::KIND_DOCUMENT, $docType = null)
    {
        if (!is_string($path) || !is_file($path)) {
            throw new \InvalidArgumentException('No file received.');
        }
        self::assertUploadSize(filesize($path));

        return self::store((string) file_get_contents($path), $ownerUserId, $originalName, $kind, $docType);
    }
    // [doc:end] saveFile

    // [doc:start] saveUploadedFile - Stores a file uploaded through a web form for the logged-in user.
    /**
     * Stores a file uploaded through a web form for the logged-in user.
     *
     * @param \yii\web\UploadedFile $file
     * @return array|null [id, url, mime, size], or null when the file was rejected (reason is logged)
     */
    public static function saveUploadedFile($file, $kind = self::KIND_DOCUMENT, $docType = null)
    {
        try {
            return self::saveFile($file->tempName, Yii::$app->user->id, $file->name, $kind, $docType);
        } catch (\InvalidArgumentException $e) {
            Yii::warning('upload rejected: ' . $e->getMessage(), 'upload');

            return null;
        }
    }
    // [doc:end] saveUploadedFile
    // [doc:start] saveBinary - Save Binary
    public static function saveBinary($bytes, $ownerUserId = null, $originalName = null, $kind = self::KIND_DOCUMENT, $docType = null)
    {
        self::assertUploadSize(strlen($bytes));

        return self::store($bytes, $ownerUserId, $originalName, $kind, $docType);
    }
    // [doc:end] saveBinary

    // [doc:start] find - Find
    /**
     * @return array|null media row (without the BLOB), or null
     */
    public static function find($uid)
    {
        if (!is_string($uid) || !preg_match('/^[a-f0-9]{32}$/', $uid)) {
            return null;
        }
        $row = (new Query())
            ->select(['id', 'uid', 'owner_user_id', 'mime', 'size', 'sha1', 'original_name', 'storage', 'bucket', 'path', 'kind', 'doc_type', 'booking_id', 'created_on'])
            ->from('media')
            ->where(['uid' => $uid])
            ->one();

        return $row ?: null;
    }
    // [doc:end] find

    // [doc:start] attachToBooking - Called when a ride photo is saved against a booking (vehicle verification). The photo is found from its address; only ride photos.
    /**
     * Called when a ride photo is saved against a booking (vehicle verification). The photo is found from its address; only ride photos
     * that are not yet tied to a ride are touched, so a photo can never be moved from one ride to another. In object storage the file
     * is copied to ride/<booking id>/<before|after>-<side>-<id>.<ext> and the old object is deleted.
     *
     * @param string $url       the address returned by the upload (contains the photo id)
     * @param int    $bookingId the ride
     * @param string $label     short words for the file name, for example "before-front" (letters, digits and dashes only)
     * @return bool whether the photo is now tied to this ride
     */
    public static function attachToBooking($url, $bookingId, $label = '')
    {
        if (!preg_match('/[a-f0-9]{32}/', (string) $url, $m)) {
            return false;
        }
        $row = self::find($m[0]);
        if (!$row || ($row['kind'] ?? '') !== self::KIND_RIDE) {
            return false;
        }
        if (!empty($row['booking_id'])) {
            return (int) $row['booking_id'] === (int) $bookingId;
        }
        $update = ['booking_id' => (int) $bookingId];
        if ($row['storage'] === self::DRIVER_S3 && !empty($row['path'])) {
            $bucket = $row['bucket'] ?: null;
            $ext = pathinfo($row['path'], PATHINFO_EXTENSION);
            $label = trim(preg_replace('/[^a-z0-9-]+/', '-', strtolower($label)), '-');
            $new = 'ride/' . (int) $bookingId . '/' . ($label !== '' ? $label . '-' : '') . $row['uid'] . '.' . $ext;
            if ($new !== $row['path']) {
                $bytes = S3Client::get($row['path'], $bucket);
                if ($bytes !== null && $bytes !== false && $bytes !== '') {
                    S3Client::put($new, $bytes, $row['mime'], $bucket);
                    Yii::$app->db->createCommand()->update('media', $update + ['path' => $new], ['id' => $row['id']])->execute();
                    S3Client::delete($row['path'], $bucket);

                    return true;
                }
            }
        }
        Yii::$app->db->createCommand()->update('media', $update, ['id' => $row['id']])->execute();

        return true;
    }
    // [doc:end] attachToBooking

    // [doc:start] forBooking - For Booking
    public static function forBooking($bookingId)
    {
        $rows = (new Query())->select(['uid', 'storage', 'bucket', 'path', 'size', 'created_on'])->from('media')
            ->where(['booking_id' => (int) $bookingId, 'kind' => self::KIND_RIDE])->all();
        $out = [];
        foreach ($rows as $r) {
            $out[$r['uid']] = $r;
        }

        return $out;
    }
    // [doc:end] forBooking

    // [doc:start] diskPath - Absolute file path for a disk-stored row, or null (db rows, missing file, unsafe path).
    /**
     * Absolute file path for a disk-stored row, or null (db rows, missing file, unsafe path).
     */
    public static function diskPath(array $row)
    {
        if (($row['storage'] ?? '') !== self::DRIVER_DISK || empty($row['path'])) {
            return null;
        }
        if (!preg_match('#^[a-f0-9]{2}/[a-f0-9]{2}/[a-f0-9]{32}$#', $row['path'])) {
            return null;
        }
        $file = self::storageRoot() . '/' . $row['path'];

        return is_file($file) ? $file : null;
    }
    // [doc:end] diskPath

    // [doc:start] remove - Remove
    public static function remove(array $row)
    {
        if (self::isLifelong($row['kind'] ?? self::KIND_DOCUMENT)) {
            throw new \LogicException('Identity documents and profile photos are kept for life and are never deleted.');
        }
        if ($row['storage'] === self::DRIVER_S3 && !empty($row['path'])) {
            S3Client::delete($row['path'], $row['bucket'] ?: null);
        } elseif ($row['storage'] === self::DRIVER_DISK && ($file = self::diskPath($row)) !== null) {
            @unlink($file);
        }
        Yii::$app->db->createCommand()->delete('media', ['id' => $row['id']])->execute();
    }
    // [doc:end] remove

    // [doc:start] dbData - Bytes of a db-stored row.
    /**
     * Bytes of a db-stored row.
     */
    public static function dbData($id)
    {
        $data = (new Query())->select('data')->from('media')->where(['id' => $id])->scalar();

        return $data === false ? null : $data;
    }
    // [doc:end] dbData

    // [doc:start] url - Url
    public static function url($uid)
    {
        // PUBLIC_BASE_URL (env or secrets 'publicBaseUrl') pins the host written into stored URLs, so a proxy or a
        // forged Host header cannot change them. Without it the host of the current request is used.
        $base = getenv('PUBLIC_BASE_URL') ?: ((require Yii::getAlias('@app/config/secrets-loader.php'))['publicBaseUrl'] ?? '');
        if ($base !== '') {
            return rtrim($base, '/') . Yii::$app->urlManager->createUrl(['/api/media/view', 'id' => $uid]);
        }

        return Yii::$app->urlManager->createAbsoluteUrl(['/api/media/view', 'id' => $uid]);
    }
    // [doc:end] url

    // [doc:start] driver - Driver
    public static function driver()
    {
        $driver = S3Client::setting('media_driver', Yii::$app->params['media']['driver'] ?? self::DRIVER_DISK);
        if ($driver === self::DRIVER_S3) {
            if (S3Client::isConfigured()) {
                return self::DRIVER_S3;
            }
            Yii::warning('media_driver is s3 but the storage keys are not set: using the local disk', 'upload');

            return self::DRIVER_DISK;
        }

        return $driver === self::DRIVER_DB ? self::DRIVER_DB : self::DRIVER_DISK;
    }
    // [doc:end] driver

    // [doc:start] storageRoot - Storage Root
    public static function storageRoot()
    {
        $dir = getenv('MEDIA_STORAGE_PATH') ?: (Yii::$app->params['media']['path'] ?? '@runtime/media');
        $dir = rtrim(str_replace('\\', '/', Yii::getAlias($dir)), '/');

        return $dir;
    }
    // [doc:end] storageRoot

    // [doc:start] maxUploadBytes - Max Upload Bytes
    public static function maxUploadBytes()
    {
        $max = (int) (Yii::$app->params['media']['maxUploadBytes'] ?? self::DEFAULT_MAX_UPLOAD);
        $max = $max > 0 ? $max : self::DEFAULT_MAX_UPLOAD;
        if (self::driver() === self::DRIVER_DB) {
            // The whole file is sent in one INSERT, so it must fit in max_allowed_packet.
            $packet = (int) Yii::$app->db->createCommand('SELECT @@max_allowed_packet')->queryScalar();
            if ($packet > 0) {
                $max = min($max, max(0, $packet - 65536));
            }
        }

        return $max;
    }
    // [doc:end] maxUploadBytes

    private static function assertUploadSize($size)
    {
        if ($size <= 0) {
            throw new \InvalidArgumentException('The file is empty.');
        }
        $max = self::maxUploadBytes();
        if ($size > $max) {
            throw new \InvalidArgumentException('The file is too large (limit ' . (int) ($max / 1024) . ' KB).');
        }
    }

    // [doc:start] isLifelong - Is Lifelong
    public static function isLifelong($kind)
    {
        return $kind !== self::KIND_RIDE;
    }
    // [doc:end] isLifelong

    // [doc:start] cleanDocType - Clean Doc Type
    public static function cleanDocType($docType, $kind)
    {
        if ($kind === self::KIND_PROFILE) {
            return 'profile_image';
        }
        if ($kind === self::KIND_RIDE) {
            return null;
        }

        return in_array($docType, self::DOC_TYPES, true) ? $docType : 'other';
    }
    // [doc:end] cleanDocType

    // [doc:start] bucketFor - Bucket For
    public static function bucketFor($kind)
    {
        $main = \app\modules\filestorage\services\S3Client::setting('media_s3_bucket');
        if (self::isLifelong($kind)) {
            return \app\modules\filestorage\services\S3Client::setting('media_s3_bucket_documents', $main);
        }

        return $main;
    }
    // [doc:end] bucketFor

    // [doc:start] objectKey - Object Key
    public static function objectKey($kind, $docType, $uid, $mime, $time = null, $ownerId = null)
    {
        $ext = self::$allowed[$mime] ?? 'bin';
        if ($kind === self::KIND_RIDE) {
            return 'ride/' . date('Y/m', $time ?: time()) . '/' . $uid . '.' . $ext;
        }
        // a file kept for life lies in the documents bucket under profile/<partner id>/<document type>/<id>.<ext>,
        // so everything of one partner's profile (documents and photo) is in one folder that can be found without the database
        return 'profile/' . ((int) $ownerId ?: 0) . '/' . ($docType ?: 'other') . '/' . $uid . '.' . $ext;
    }
    // [doc:end] objectKey

    // [doc:start] cleanKind - Clean Kind
    public static function cleanKind($kind)
    {
        return in_array($kind, [self::KIND_PROFILE, self::KIND_RIDE], true) ? $kind : self::KIND_DOCUMENT;
    }
    // [doc:end] cleanKind

    /**
     * @return array [longest side in px, jpeg/webp quality] for a kind
     */
    private static function shrinkFor($kind)
    {
        if ($kind === self::KIND_RIDE) {
            return [1280, 72];
        }
        if ($kind === self::KIND_PROFILE) {
            return [800, 80];
        }
        $max = (int) (Yii::$app->params['media']['maxDimension'] ?? self::DEFAULT_MAX_DIMENSION);
        $q = (int) (Yii::$app->params['media']['jpegQuality'] ?? self::DEFAULT_JPEG_QUALITY);

        return [$max > 0 ? $max : self::DEFAULT_MAX_DIMENSION, $q > 0 ? $q : self::DEFAULT_JPEG_QUALITY];
    }

    private static function store($bytes, $ownerUserId, $originalName, $kind = self::KIND_DOCUMENT, $docType = null)
    {
        $kind = self::cleanKind($kind);
        $docType = self::cleanDocType($docType, $kind);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        if (!isset(self::$allowed[$mime])) {
            throw new \InvalidArgumentException('Only JPEG, PNG, WebP, GIF and PDF files are allowed.');
        }
        if (strpos($mime, 'image/') === 0) {
            $info = @getimagesizefromstring($bytes);
            if ($info === false) {
                throw new \InvalidArgumentException('The file is not a valid image.');
            }
            if ($info[0] * $info[1] > self::MAX_PIXELS) {
                throw new \InvalidArgumentException('The image dimensions are too large.');
            }
            $bytes = self::optimizeImage($bytes, $mime, $kind);
        }

        $sha1 = sha1($bytes);
        if ($ownerUserId !== null) {
            // Same owner uploading the same file again: reuse the stored copy.
            $existing = (new Query())->select('uid')->from('media')
                ->where(['owner_user_id' => $ownerUserId, 'sha1' => $sha1])->scalar();
            if ($existing) {
                return ['id' => $existing, 'url' => self::url($existing), 'mime' => $mime, 'size' => strlen($bytes)];
            }
        }

        $uid = bin2hex(random_bytes(16));
        $name = $originalName ? preg_replace('/[^A-Za-z0-9._-]+/', '_', basename($originalName)) : null;
        $row = [
            'uid' => $uid,
            'owner_user_id' => $ownerUserId,
            'mime' => $mime,
            'size' => strlen($bytes),
            'sha1' => $sha1,
            'original_name' => $name ? substr($name, 0, 255) : null,
            'kind' => $kind,
            'doc_type' => $docType,
            'created_on' => date('Y-m-d H:i:s'),
        ];

        $file = null;
        $object = null;
        $bucket = null;
        if (self::driver() === self::DRIVER_S3) {
            // a folder per document type (ride photos in ride/), and the documents bucket for what is kept for life
            $bucket = self::bucketFor($kind);
            $object = self::objectKey($kind, $docType, $uid, $mime, null, $ownerUserId);
            S3Client::put($object, $bytes, $mime, $bucket);
            $row += ['storage' => self::DRIVER_S3, 'bucket' => $bucket, 'path' => $object, 'data' => null];
        } elseif (self::driver() === self::DRIVER_DISK) {
            $relative = substr($uid, 0, 2) . '/' . substr($uid, 2, 2) . '/' . $uid;
            $file = self::writeFile($relative, $bytes);
            $row += ['storage' => self::DRIVER_DISK, 'path' => $relative, 'data' => null];
        } else {
            $row += ['storage' => self::DRIVER_DB, 'path' => null, 'data' => $bytes];
        }

        try {
            Yii::$app->db->createCommand()->insert('media', $row)->execute();
        } catch (\Throwable $e) {
            if ($file !== null) {
                @unlink($file);
            }
            if ($object !== null) {
                S3Client::delete($object, $bucket);
            }
            throw $e;
        }

        return ['id' => $uid, 'url' => self::url($uid), 'mime' => $mime, 'size' => strlen($bytes)];
    }

    // [doc:start] writeFile - Writes bytes to the storage root atomically and returns the absolute file path.
    /**
     * Writes bytes to the storage root atomically and returns the absolute file path.
     */
    public static function writeFile($relative, $bytes)
    {
        $root = self::storageRoot();
        $file = $root . '/' . $relative;
        $dir = dirname($file);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Cannot create media directory.');
        }
        self::protectRoot($root);

        $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (file_put_contents($tmp, $bytes, LOCK_EX) === false || !rename($tmp, $file)) {
            @unlink($tmp);
            throw new \RuntimeException('Cannot write media file.');
        }

        return $file;
    }
    // [doc:end] writeFile

    /**
     * Keeps a web server from serving the storage directory directly if it happens to sit under the web root.
     */
    private static function protectRoot($root)
    {
        $guard = $root . '/.htaccess';
        if (!is_file($guard)) {
            @file_put_contents($guard, "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
        }
    }

    /**
     * Applies EXIF orientation, downscales large images and re-encodes (strips metadata incl. GPS).
     * Falls back to the original bytes whenever re-encoding is not possible or would not help.
     */
    private static function optimizeImage($bytes, $mime, $kind = self::KIND_DOCUMENT)
    {
        if ($mime === 'image/gif' || !function_exists('imagecreatefromstring')) {
            return $bytes; // animated GIFs and hosts without GD are kept untouched
        }

        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            return $bytes;
        }

        $changed = false;
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data('data://image/jpeg;base64,' . base64_encode($bytes));
            $angle = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 1] ?? 0;
            if ($angle !== 0) {
                $rotated = imagerotate($image, $angle, 0);
                if ($rotated !== false) {
                    imagedestroy($image);
                    $image = $rotated;
                    $changed = true;
                }
            }
        }

        [$max, $quality] = self::shrinkFor($kind);
        $w = imagesx($image);
        $h = imagesy($image);
        if (max($w, $h) > $max) {
            $scale = $max / max($w, $h);
            $resized = imagescale($image, max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale)), IMG_BICUBIC);
            if ($resized !== false) {
                imagedestroy($image);
                $image = $resized;
                $changed = true;
            }
        }

        ob_start();
        switch ($mime) {
            case 'image/png':
                imagealphablending($image, false);
                imagesavealpha($image, true);
                imagepng($image, null, 9);
                break;
            case 'image/webp':
                imagewebp($image, null, $quality);
                break;
            default:
                imageinterlace($image, true);
                imagejpeg($image, null, $quality);
        }
        $out = (string) ob_get_clean();
        imagedestroy($image);

        // Keep the re-encoded copy when it is smaller, or when it carries a needed change (rotation/resize).
        return ($out !== '' && ($changed || strlen($out) < strlen($bytes))) ? $out : $bytes;
    }
}
