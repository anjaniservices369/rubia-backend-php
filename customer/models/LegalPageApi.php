<?php

namespace app\modules\admin\models;

use Yii;

/**
 * Answer of the public "legal-page" endpoint that every app has: its own Terms and Conditions, Privacy Policy
 * or Refund Policy, written in the admin dashboard (Terms & Policies) and kept per app in page.app_key.
 *
 *   GET /api/user/legal-page?type=terms|privacy|refund     customer app
 *   GET /api/driver/legal-page?type=...                    driver partner app (Halting drivers)
 *   GET /api/car|auto|bike/legal-page?type=...             taxi partner apps
 */
class LegalPageApi
{
    /** type name used in the URL => Page type */
    const TYPES = [
        'terms' => Page::TYPE_TERMS,
        'privacy' => Page::TYPE_PRIVACY,
        'refund' => Page::TYPE_REFUND,
    ];

    /**
     * @param string $app one of Page::APP_*
     * @param string|null $type terms, privacy or refund (default terms)
     * @return array the JSON body: status OK + details, or status NOK + error
     */
    public static function response($app, $type)
    {
        $type = $type === null || $type === '' ? 'terms' : (string) $type;
        if (!isset(self::TYPES[$type])) {
            return ['status' => 'NOK', 'error' => Yii::t('app', 'Unknown page type')];
        }
        $page = Page::findPublished($app, self::TYPES[$type]);
        if ($page === null || trim(strip_tags((string) $page->description)) === '') {
            return ['status' => 'NOK', 'error' => Yii::t('app', 'This page has not been published yet')];
        }
        return [
            'status' => 'OK',
            'details' => [
                'title' => $page->title,
                'description' => $page->description,
                'updated_on' => $page->updated_date ?: $page->created_date,
            ],
        ];
    }
}
