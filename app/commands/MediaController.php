<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Query;
use app\modules\filestorage\services\MediaStore;

/**
 * Operations for uploaded images/documents.
 *
 *   php yii media/stats                    storage usage, driver and limits
 *   php yii media/move-to-disk [--limit=N] move BLOBs out of the database into files
 *   php yii media/move-to-storage [--limit=N] [--apply]   move files (disk or database) to the object storage (media_driver=s3)
 *   php yii media/prune-rides [--keep=N] [--apply]        delete ride photos (selfie, car photos) older than N days (default: setting media_ride_retention_days)
 *   php yii media/purge-orphans [--days=30] [--apply]   list (or delete) files no record points to
 */
class MediaController extends Controller
{
    /** @var int */
    public $limit = 500;
    /** @var int files younger than this many days are never purged */
    public $days = 30;
    /** @var int|null days to keep ride photos in prune-rides */
    public $keep = null;
    /** @var bool actually delete in purge-orphans (default is a dry run) */
    public $apply = false;

    /** Columns that hold image URLs (table => columns). Missing tables/columns are skipped. */
    private static $referencing = [
        'user' => ['profile_image'],
        'narsing' => ['profile_image'],
        'driver_aadhar_card' => ['aadhar_card_front', 'aadhar_card_back'],
        'driver_pan_cards' => ['pan_card'],
        'driving_license' => ['driving_license_front', 'driving_license_back'],
        'vehicle_verification' => ['image'],
        'driver_details' => ['proof_of_license', 'proof_of_license_back', 'rc_proof', 'rc_proof_back',
            'adhaar_front', 'adhaar_back', 'pan_front', 'chassis_image'],
        'vehicals' => ['image'],
        'vehicle_types' => ['image'],
        'services' => ['image'],
        'banner' => ['image'],
    ];

    // [doc:start] options - Options
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['limit', 'days', 'apply', 'keep']);
    }
    // [doc:end] options

    // [doc:start] actionMoveToStorage - Copies files from the local disk or the database into the object storage, checks each copy, then removes the local one.
    /**
     * Copies files from the local disk or the database into the object storage, checks each copy, then removes the local one.
     * A dry run unless --apply is given. Safe to run again: rows already in the storage are skipped.
     */
    public function actionMoveToStorage()
    {
        if (!\app\modules\filestorage\services\S3Client::isConfigured()) {
            $this->stderr("The object storage keys are not set (media_s3_* settings).\n");

            return ExitCode::CONFIG;
        }
        $rows = (new Query())->from('media')->where(['<>', 'storage', 's3'])->orderBy('id')->limit($this->limit)->all();
        $moved = 0;
        foreach ($rows as $r) {
            $bytes = $r['storage'] === 'db' ? $r['data'] : (($f = MediaStore::diskPath($r)) ? file_get_contents($f) : null);
            if ($bytes === null || $bytes === false || $bytes === '') {
                $this->stdout("#{$r['id']} {$r['uid']}: file missing, skipped\n");
                continue;
            }
            $kind = $r['kind'] ?: 'document';
            $docType = MediaStore::cleanDocType($r['doc_type'] ?? null, $kind);
            $bucket = MediaStore::bucketFor($kind);
            $key = MediaStore::objectKey($kind, $docType, $r['uid'], $r['mime'], strtotime($r['created_on']), $r['owner_user_id'] ?? null);
            if (!$this->apply) {
                $this->stdout("would move #{$r['id']} ({$r['storage']}, " . strlen($bytes) . " bytes) to $key\n");
                continue;
            }
            \app\modules\filestorage\services\S3Client::put($key, $bytes, $r['mime'], $bucket);
            $same = \app\modules\filestorage\services\S3Client::get($key, $bucket) === $bytes;
            if (!$same) {
                $this->stderr("#{$r['id']}: the copy does not match, kept locally\n");
                continue;
            }
            Yii::$app->db->createCommand()->update('media', ['storage' => 's3', 'bucket' => $bucket, 'doc_type' => $docType, 'path' => $key, 'data' => null], ['id' => $r['id']])->execute();
            if ($r['storage'] === 'disk' && ($f = MediaStore::diskPath($r))) {
                @unlink($f);
            }
            $moved++;
        }
        $this->stdout($this->apply ? "moved $moved file(s)\n" : "dry run - add --apply to move them\n");

        return ExitCode::OK;
    }
    // [doc:end] actionMoveToStorage

    // [doc:start] actionPruneRides - Deletes ride photos (kind=ride: selfie and car photos) older than the retention period (setting media_ride_retention_days,.
    /**
     * Deletes ride photos (kind=ride: selfie and car photos) older than the retention period (setting media_ride_retention_days,
     * default 180, or --keep). Documents and profile photos are never touched. A dry run unless --apply is given.
     */
    public function actionPruneRides()
    {
        $days = $this->keep !== null ? (int) $this->keep : (int) \app\modules\filestorage\services\S3Client::setting('media_ride_retention_days', '180');
        $cut = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));
        $rows = (new Query())->from('media')->where(['kind' => 'ride'])->andWhere(['<', 'created_on', $cut])->limit($this->limit * 20)->all();
        $bytes = 0;
        foreach ($rows as $r) {
            $bytes += (int) $r['size'];
            if ($this->apply) {
                MediaStore::remove($r);
            }
        }
        $this->stdout(sprintf("%s %d ride photo(s) older than %d days (%.1f MB)\n", $this->apply ? 'deleted' : 'would delete', count($rows), $days, $bytes / 1048576));
        if (!$this->apply) {
            $this->stdout("dry run - add --apply to delete them\n");
        }

        return ExitCode::OK;
    }
    // [doc:end] actionPruneRides

    // [doc:start] actionStats - Stats
    public function actionStats()
    {
        $db = Yii::$app->db;
        $this->stdout('Driver: ' . MediaStore::driver() . "\n");
        $this->stdout('Storage path: ' . MediaStore::storageRoot() . "\n");
        $this->stdout('Max upload: ' . round(MediaStore::maxUploadBytes() / 1048576, 1) . " MB\n");
        $this->stdout('DB max_allowed_packet: ' .
            round($db->createCommand('SELECT @@max_allowed_packet')->queryScalar() / 1048576, 1) . " MB\n\n");

        $rows = (new Query())->select(['storage', 'n' => 'COUNT(*)', 'bytes' => 'COALESCE(SUM(size),0)'])
            ->from('media')->groupBy('storage')->all();
        $total = 0;
        foreach ($rows as $r) {
            $total += $r['bytes'];
            $this->stdout(sprintf("%-6s files=%d size=%.1f MB (avg %.0f KB)\n", $r['storage'], $r['n'],
                $r['bytes'] / 1048576, $r['n'] ? $r['bytes'] / $r['n'] / 1024 : 0));
        }
        $this->stdout(sprintf("total  %.1f MB\n", $total / 1048576));

        $root = MediaStore::storageRoot();
        if (is_dir($root)) {
            $this->stdout(sprintf("disk free at storage path: %.1f GB\n", disk_free_space($root) / 1073741824));
        }

        $last = (new Query())->select(['d' => 'DATE(created_on)', 'n' => 'COUNT(*)', 'bytes' => 'SUM(size)'])
            ->from('media')->where(['>=', 'created_on', date('Y-m-d', strtotime('-7 days'))])
            ->groupBy('DATE(created_on)')->orderBy('d')->all();
        if ($last) {
            $this->stdout("\nlast 7 days:\n");
            foreach ($last as $r) {
                $this->stdout(sprintf("  %s  %d files  %.1f MB\n", $r['d'], $r['n'], $r['bytes'] / 1048576));
            }
        }

        return ExitCode::OK;
    }
    // [doc:end] actionStats

    // [doc:start] actionMoveToDisk - Move To Disk
    public function actionMoveToDisk()
    {
        $moved = 0;
        $ids = (new Query())->select('id')->from('media')->where(['storage' => MediaStore::DRIVER_DB])
            ->orderBy('id')->limit((int) $this->limit)->column();
        foreach ($ids as $id) {
            $row = (new Query())->from('media')->where(['id' => $id])->one();
            if (!$row || $row['data'] === null) {
                continue;
            }
            $relative = substr($row['uid'], 0, 2) . '/' . substr($row['uid'], 2, 2) . '/' . $row['uid'];
            $file = MediaStore::writeFile($relative, $row['data']);
            if (sha1_file($file) !== $row['sha1']) {
                @unlink($file);
                $this->stderr("checksum mismatch for media {$row['id']}, left in database\n");
                continue;
            }
            Yii::$app->db->createCommand()->update('media', [
                'storage' => MediaStore::DRIVER_DISK, 'path' => $relative, 'data' => null,
            ], ['id' => $id])->execute();
            $moved++;
        }
        $this->stdout("moved $moved file(s) to disk\n");

        return ExitCode::OK;
    }
    // [doc:end] actionMoveToDisk

    // [doc:start] actionPurgeOrphans - Purge Orphans
    public function actionPurgeOrphans()
    {
        $db = Yii::$app->db;
        $schema = $db->schema;

        $cutoff = date('Y-m-d H:i:s', strtotime('-' . (int) $this->days . ' days'));
        $candidates = (new Query())->select(['id', 'uid', 'storage', 'path', 'size'])
            ->from('media')->where(['<', 'created_on', $cutoff])
            // identity documents and profile photos are kept for life, even when nothing points to them any more (an older version of a licence)
            ->andWhere(['kind' => 'ride'])->all();

        $referenced = [];
        foreach (self::$referencing as $table => $columns) {
            if ($schema->getTableSchema($table, true) === null) {
                continue;
            }
            foreach ($columns as $column) {
                if (!$schema->getTableSchema($table)->getColumn($column)) {
                    continue;
                }
                $values = (new Query())->select($column)->from($table)->where(['like', $column, 'media/view'])->column();
                foreach ($values as $value) {
                    if (preg_match_all('/[a-f0-9]{32}/', (string) $value, $m)) {
                        foreach ($m[0] as $uid) {
                            $referenced[$uid] = true;
                        }
                    }
                }
            }
        }

        $orphans = array_filter($candidates, function ($c) use ($referenced) {
            return !isset($referenced[$c['uid']]);
        });
        $bytes = array_sum(array_column($orphans, 'size'));
        $this->stdout(sprintf("%d of %d older-than-%dd file(s) are unreferenced (%.1f MB)\n",
            count($orphans), count($candidates), $this->days, $bytes / 1048576));

        if (!$this->apply) {
            $this->stdout("dry run - add --apply to delete them\n");
            return ExitCode::OK;
        }

        foreach ($orphans as $o) {
            if ($o['storage'] === MediaStore::DRIVER_DISK && $o['path']) {
                $file = MediaStore::diskPath($o);
                if ($file) {
                    @unlink($file);
                }
            }
            $db->createCommand()->delete('media', ['id' => $o['id']])->execute();
        }
        $this->stdout('deleted ' . count($orphans) . " file(s)\n");

        return ExitCode::OK;
    }
    // [doc:end] actionPurgeOrphans
}