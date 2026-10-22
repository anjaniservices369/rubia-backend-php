<?php

namespace app\commands;

use app\models\AdminAuditLog;
use app\models\User;
use app\validators\PasswordStrengthValidator;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Bootstraps and manages trusted operator (super-admin) accounts from the command line, since there is no
 * in-app way to create the very first one (every in-app path, UsersController::actionCreate included,
 * requires an already logged-in admin to do the creating).
 *
 * None of these commands edit app/config/params.php's 'superAdminEmails' for you. That list is a reviewed,
 * version-controlled config file on purpose: it is the actual access-control gate (app/forms/LoginForm), and
 * keeping it a manual, deploy-time edit means every grant and revoke leaves a code-review trail independent
 * of whoever has shell access to run these commands. Each command instead tells you exactly what to edit.
 */
class AdminController extends Controller
{
    /** @var bool revoke-super-admin only: also set the account's status to blocked immediately */
    public $block = false;

    // [doc:start] options - Options
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), $actionID === 'revoke-super-admin' ? ['block'] : []);
    }
    // [doc:end] options

    // [doc:start] actionCreateSuperAdmin - yii admin/create-super-admin <email> - Creates a new user_role=admin account. Prompts for the password if it is not given on the command line.
    /**
     * Creates a new user_role = admin account.
     *
     * Creating the database row is only half of what makes someone a super admin: app/forms/LoginForm also
     * requires the email to be listed in 'superAdminEmails' in app/config/params.php before it can sign in.
     *
     * @param string $email
     * @param string|null $password Plaintext password. Omit it to be prompted (not echoed to the terminal).
     *
     * @return int exit code
     */
    public function actionCreateSuperAdmin($email, $password = null)
    {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->stdout("'$email' is not a valid email address.\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }

        if (User::find()->where(['username' => $email])->exists()) {
            $this->stdout("A user with username '$email' already exists.\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }

        if ($password === null) {
            $password = $this->prompt('Password for the new account (min 8 characters, at least one letter and one number):');
        }

        $validator = new PasswordStrengthValidator();
        if (!$validator->validate($password, $error)) {
            $this->stdout("$error\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $user = new User();
        $user->username = $email;
        $user->email = $email;
        $user->user_role = User::ROLE_ADMIN;
        $user->status = User::STATUS_ACTIVE;
        $user->setPassword($password);
        $user->generateAuthKey();

        if (!$user->save()) {
            $this->stdout("Could not save the account:\n", Console::FG_RED);
            foreach ($user->errors as $attribute => $messages) {
                $this->stdout("  $attribute: " . implode(', ', $messages) . "\n", Console::FG_RED);
            }
            return ExitCode::UNSPECIFIED_ERROR;
        }

        AdminAuditLog::record(AdminAuditLog::ACTION_CREATE_SUPER_ADMIN, $email);

        $this->stdout("Created user id {$user->id} ($email) with user_role=admin.\n", Console::FG_GREEN);
        $this->stdout(
            "This account cannot sign in yet: add '$email' to 'superAdminEmails' in app/config/params.php "
            . "and deploy that change first.\n",
            Console::FG_YELLOW
        );

        return ExitCode::OK;
    }
    // [doc:end] actionCreateSuperAdmin

    // [doc:start] actionRevokeSuperAdmin - yii admin/revoke-super-admin <email> [--block] - Records a revoke request and reminds you to remove the email from params.php. --block also blocks the account immediately.
    /**
     * Records that super-admin access should be revoked for $email, and reminds the operator to remove it
     * from 'superAdminEmails'. That file edit is the real revoke; this command does not make it for you
     * (same reasoning as create-super-admin). With --block, the account's status is also set to blocked
     * immediately, which (independent of params.php) stops it signing in at all, anywhere in the app --
     * useful when the deploy that drops it from the allowlist is not going out right away.
     *
     * @param string $email
     *
     * @return int exit code
     */
    public function actionRevokeSuperAdmin($email)
    {
        $email = trim($email);
        $user = User::find()->where(['username' => $email])->one();

        if ($user === null) {
            $this->stdout("No user with username '$email'.\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }

        AdminAuditLog::record(
            AdminAuditLog::ACTION_REVOKE_REQUESTED,
            $email,
            "user_role was '{$user->user_role}', status was '{$user->status}'" . ($this->block ? ' (--block passed)' : '')
        );

        $this->stdout(
            "Remove '$email' from 'superAdminEmails' in app/config/params.php and deploy that change -- "
            . "this command does not edit it for you.\n",
            Console::FG_YELLOW
        );

        if ($this->block) {
            $user->status = User::STATUS_BLOCKED;
            if (!$user->save(false)) {
                $this->stdout("Could not block the account.\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
            AdminAuditLog::record(AdminAuditLog::ACTION_BLOCKED, $email);
            $this->stdout("Account blocked immediately (status = blocked): it cannot sign in anywhere until unblocked.\n", Console::FG_GREEN);
        }

        return ExitCode::OK;
    }
    // [doc:end] actionRevokeSuperAdmin

    // [doc:start] actionListSuperAdmins - yii admin/list-super-admins - Cross-checks user_role=admin accounts against the superAdminEmails allowlist and shows recent audit log entries.
    /**
     * Lists every user_role = admin account and flags where the database and the allowlist disagree:
     *   - an admin-role account whose email is NOT on the allowlist cannot sign in (dormant, e.g. a test
     *     account, or one whose access was dropped from params.php but whose row was never cleaned up);
     *   - an allowlist entry with no matching account is a dangling config line (typo, or the account was
     *     since deleted/renamed) -- it grants nothing, but it is worth cleaning up.
     * Also prints the most recent admin_audit_log entries for context.
     *
     * @return int exit code
     */
    public function actionListSuperAdmins()
    {
        $allowed = array_map('strtolower', Yii::$app->params['superAdminEmails'] ?? []);
        $admins = User::find()->where(['user_role' => User::ROLE_ADMIN])->all();

        $this->stdout("user_role=admin accounts:\n", Console::FG_YELLOW);
        $matchedAllowlist = [];
        foreach ($admins as $user) {
            $isAllowed = in_array(strtolower($user->username), $allowed, true)
                || in_array(strtolower((string) $user->email), $allowed, true);
            if ($isAllowed) {
                $matchedAllowlist[] = strtolower($user->username);
            }
            $flag = $isAllowed ? '' : '  <-- NOT on superAdminEmails, cannot sign in';
            $this->stdout(sprintf("  id %-6d %-40s status=%-8s%s\n", $user->id, $user->username, $user->status, $flag));
        }

        $this->stdout("\nsuperAdminEmails entries with no matching account:\n", Console::FG_YELLOW);
        $dangling = array_diff($allowed, $matchedAllowlist);
        if (!$dangling) {
            $this->stdout("  (none)\n");
        } else {
            foreach ($dangling as $email) {
                $this->stdout("  $email  <-- dangling config entry, grants nothing\n");
            }
        }

        $this->stdout("\nrecent admin_audit_log entries:\n", Console::FG_YELLOW);
        $entries = AdminAuditLog::find()->orderBy(['created_at' => SORT_DESC])->limit(20)->all();
        if (!$entries) {
            $this->stdout("  (none)\n");
        }
        foreach ($entries as $entry) {
            $this->stdout(sprintf(
                "  %s  %-32s %-40s by %s@%s%s\n",
                $entry->created_at,
                $entry->action,
                $entry->target_username,
                $entry->performed_by_os_user ?: '?',
                $entry->performed_from_host ?: '?',
                $entry->note ? "  ({$entry->note})" : ''
            ));
        }

        return ExitCode::OK;
    }
    // [doc:end] actionListSuperAdmins
}
