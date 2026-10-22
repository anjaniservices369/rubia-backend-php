<?php

namespace app\commands;

use app\modules\admin\models\SettingGuide;
use app\modules\admin\models\SettingVault;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Query;

/**
 * The protected dashboard settings (API secrets, passwords, tokens).
 *
 *   php yii settings/status            which settings are protected, whether they are set and whether they are saved encrypted
 *   php yii settings/protect [--apply] encrypt the protected values that were saved as plain text (a dry run unless --apply)
 */
class SettingsController extends Controller
{
    /** @var bool actually encrypt (default is a dry run) */
    public $apply = false;

    // [doc:start] options - Options
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['apply']);
    }
    // [doc:end] options

    // [doc:start] actionStatus - Which protected settings exist, whether they are set and whether they are encrypted. Never prints a value.
    /**
     * Which protected settings exist, whether they are set and whether they are encrypted. Never prints a value.
     */
    public function actionStatus()
    {
        $this->stdout('Encryption key available: ' . (SettingVault::isAvailable() ? 'yes' : 'NO (set SETTINGS_ENCRYPTION_KEY)') . "\n\n");
        $rows = (new Query())->select(['setting_key', 'value'])->from('web_setting')->where(['setting_key' => SettingGuide::secretKeys()])->all();
        foreach ($rows as $r) {
            $state = (string) $r['value'] === '' ? 'not set' : (SettingVault::isEncrypted($r['value']) ? 'saved, encrypted' : 'saved as PLAIN TEXT (run settings/protect --apply)');
            $this->stdout(sprintf("%-34s %s\n", $r['setting_key'], $state));
        }

        return ExitCode::OK;
    }
    // [doc:end] actionStatus

    // [doc:start] actionProtect - Encrypts the protected values that were saved as plain text. A dry run unless --apply is given. Safe to run again.
    /**
     * Encrypts the protected values that were saved as plain text. A dry run unless --apply is given. Safe to run again.
     */
    public function actionProtect()
    {
        if (!SettingVault::isAvailable()) {
            $this->stderr("There is no encryption key text (set SETTINGS_ENCRYPTION_KEY on the server).\n");

            return ExitCode::CONFIG;
        }
        $rows = (new Query())->select(['setting_id', 'setting_key', 'value'])->from('web_setting')
            ->where(['setting_key' => SettingGuide::secretKeys()])->andWhere(['<>', 'value', ''])->all();
        $n = 0;
        foreach ($rows as $r) {
            if (SettingVault::isEncrypted($r['value'])) {
                continue;
            }
            $n++;
            $this->stdout(($this->apply ? 'encrypting ' : 'would encrypt ') . $r['setting_key'] . "\n");
            if ($this->apply) {
                Yii::$app->db->createCommand()->update('web_setting', ['value' => SettingVault::encrypt($r['value'])], ['setting_id' => $r['setting_id']])->execute();
            }
        }
        $this->stdout($n === 0 ? "nothing to do: every saved protected value is already encrypted\n" : ($this->apply ? "done\n" : "dry run - add --apply to encrypt them\n"));

        return ExitCode::OK;
    }
    // [doc:end] actionProtect
}
