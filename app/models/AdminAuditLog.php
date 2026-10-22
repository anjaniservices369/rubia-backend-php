<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Audit trail for super-admin lifecycle events, written by the `admin/*` console commands (see
 * app/commands/AdminController.php). Table: admin_audit_log (database/migrations/025_admin_audit_log).
 *
 * This only ever records that a command ran, as whoever's OS session ran it -- it is not a substitute for
 * the superAdminEmails allowlist in params.php, which is the actual access control.
 *
 * @property int $id
 * @property string $action
 * @property string $target_username
 * @property string|null $performed_by_os_user
 * @property string|null $performed_from_host
 * @property string|null $note
 * @property string $created_at
 */
class AdminAuditLog extends ActiveRecord
{
    const ACTION_CREATE_SUPER_ADMIN = 'create_super_admin';
    const ACTION_REVOKE_REQUESTED = 'revoke_super_admin_requested';
    const ACTION_BLOCKED = 'blocked';
    const ACTION_PASSWORD_RESET_COMPLETED = 'password_reset_completed';

    public static function tableName()
    {
        return 'admin_audit_log';
    }

    public function rules()
    {
        return [
            [['action', 'target_username'], 'required'],
            [['action', 'target_username', 'performed_by_os_user', 'performed_from_host'], 'string', 'max' => 255],
            ['note', 'string'],
        ];
    }

    /**
     * Records one event. Best-effort OS user/host are filled in automatically.
     */
    public static function record($action, $targetUsername, $note = null)
    {
        $entry = new self();
        $entry->action = $action;
        $entry->target_username = $targetUsername;
        $entry->performed_by_os_user = @get_current_user() ?: null;
        $entry->performed_from_host = @gethostname() ?: null;
        $entry->note = $note;
        $entry->save(false);

        return $entry;
    }
}
