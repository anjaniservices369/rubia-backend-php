<?php

namespace app\modules\filestorage\controllers;


use app\modules\api\controllers\BKController;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii\web\UploadedFile;
use app\modules\auth\services\AuthSettings;
use app\modules\filestorage\services\MediaStore;

/**
 * Image/document storage for the mobile apps (replaces uploading to Firebase Storage).
 *
 * POST /api/media/upload   header auth_code; multipart field "file" (or "image_base64"); optional field "kind"
 *                          (document | profile | ride: how much the photo is shrunk and how long it is kept, default document) and, for a
 *                          document, doc_type (driving_license | aadhar | pan_card | other; a profile photo is always profile_image)
 *                          -> {status:"OK", url, id, mime, size}. Send `url` in the existing image fields.
 * GET  /api/media/view?id=<id>  the stored file (the unguessable id is the access token). A file on disk is streamed; a file in the
 *                          object storage is sent with a short redirect, so the bytes never pass through PHP.
 */
class MediaController extends BKController
{
    // [doc:start] behaviors - Behaviors
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'access' => [
                'rules' => [
                    [
                        'actions' => ['upload', 'view'],
                        'allow' => true,
                        'roles' => ['?', '@'],
                    ],
                ],
            ],
        ]);
    }
    // [doc:end] behaviors

    // [doc:start] actionUpload - /api/media/upload - Upload
    public function actionUpload()
    {
        $data = [];

        $code = Yii::$app->request->headers->get('auth_code');
        if ($code === null) {
            $code = Yii::$app->request->get('auth_code');
        }
        $userId = $code ? (new AuthSettings())->getAuthSession($code) : null;
        if (empty($userId)) {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t('app', 'User Not Found');
            return $this->sendJsonResponse($data);
        }

        try {
            $file = UploadedFile::getInstanceByName('file');
            if ($file !== null && $file->error === UPLOAD_ERR_OK) {
                $saved = MediaStore::saveFile($file->tempName, $userId, $file->name, MediaStore::cleanKind(Yii::$app->request->post('kind')), Yii::$app->request->post('doc_type'));
            } elseif ($file !== null) {
                throw new \InvalidArgumentException('Upload failed (code ' . $file->error . ').');
            } else {
                $b64 = (string) Yii::$app->request->post('image_base64', '');
                $b64 = preg_replace('#^data:[^;]+;base64,#', '', $b64);
                $bytes = $b64 !== '' ? base64_decode($b64, true) : false;
                if ($bytes === false) {
                    throw new \InvalidArgumentException('Send the file as multipart field "file" or as "image_base64".');
                }
                $saved = MediaStore::saveBinary($bytes, $userId, Yii::$app->request->post('file_name'), MediaStore::cleanKind(Yii::$app->request->post('kind')), Yii::$app->request->post('doc_type'));
            }

            $data['status'] = self::API_OK;
            $data += $saved;
        } catch (\InvalidArgumentException $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        } catch (\Throwable $e) {
            Yii::error('media upload failed: ' . $e->getMessage(), 'upload');
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t('app', 'Upload failed');
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionUpload

    // [doc:start] actionView - /api/media/view - View
    public function actionView($id = '')
    {
        $row = MediaStore::find($id);
        if ($row === null) {
            throw new \yii\web\NotFoundHttpException('File not found.');
        }

        $response = Yii::$app->response;
        $etag = '"' . $row['sha1'] . '"';
        $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        $response->headers->set('ETag', $etag);
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        if (Yii::$app->request->headers->get('If-None-Match') === $etag) {
            $response->format = Response::FORMAT_RAW;
            $response->statusCode = 304;
            return '';
        }

        if ($row['storage'] === MediaStore::DRIVER_S3) {
            // Documents (Aadhaar, PAN, licence ...) are private: a link that works for 15 minutes. Profile and ride photos use the
            // public CDN address when one is set, otherwise the same short link.
            $public = \app\modules\filestorage\services\S3Client::setting('media_public_base_url');
            if ($public !== '' && ($row['kind'] ?? 'document') !== MediaStore::KIND_DOCUMENT) {
                $target = rtrim($public, '/') . '/' . $row['path'];
                $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
            } else {
                $target = \app\modules\filestorage\services\S3Client::presignedGet($row['path'], 900, $row['bucket'] ?: null);
                $response->headers->set('Cache-Control', 'private, max-age=600');
            }

            return $response->redirect($target, 302);
        }

        $path = MediaStore::diskPath($row);
        if ($path !== null) {
            // Streams from disk with HTTP Range support.
            return $response->sendFile($path, null, ['mimeType' => $row['mime'], 'inline' => true]);
        }

        $data = $row['storage'] === 'db' ? MediaStore::dbData($row['id']) : null;
        if ($data === null) {
            throw new \yii\web\NotFoundHttpException('File not found.');
        }
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', $row['mime']);
        $response->headers->set('Content-Length', (string) $row['size']);
        $response->headers->set('Content-Disposition', 'inline');

        return $data;
    }
    // [doc:end] actionView
}