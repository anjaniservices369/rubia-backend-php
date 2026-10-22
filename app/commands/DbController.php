<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Versioned database changes from database/migrations (NNN_name.up.sql / NNN_name.down.sql).
 *
 *   php yii db/status           which scripts are applied
 *   php yii db/up --yes         apply all pending scripts in order
 *   php yii db/down --yes       revert the most recently applied script
 *
 * Take a backup first (mysqldump). DDL statements are not transactional in MySQL, so a failed script can be
 * partly applied: restore the backup and fix the script before running again.
 */
class DbController extends Controller
{
    /** @var bool required for anything that changes the database */
    public $yes = false;

    // [doc:start] options - Options
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['yes']);
    }
    // [doc:end] options

    // [doc:start] actionStatus - Status
    public function actionStatus()
    {
        $applied = $this->applied();
        foreach ($this->scripts() as $name => $files) {
            $this->stdout(sprintf("%-8s %s\n", isset($applied[$name]) ? 'applied' : 'PENDING', $name));
        }

        return ExitCode::OK;
    }
    // [doc:end] actionStatus

    // [doc:start] actionUp - Up
    public function actionUp()
    {
        $applied = $this->applied();
        $pending = array_diff_key($this->scripts(), $applied);
        if (!$pending) {
            $this->stdout("nothing to apply\n");
            return ExitCode::OK;
        }
        foreach ($pending as $name => $files) {
            $this->stdout("pending: $name\n");
        }
        if (!$this->yes) {
            $this->stdout("dry run - add --yes to apply (backup first)\n");
            return ExitCode::OK;
        }

        foreach ($pending as $name => $files) {
            $this->stdout("applying $name ... ");
            $this->executeFile($files['up']);
            Yii::$app->db->createCommand()->insert('db_migration_log', ['name' => $name, 'applied_on' => date('Y-m-d H:i:s')])->execute();
            $this->stdout("done\n");
        }

        return ExitCode::OK;
    }
    // [doc:end] actionUp

    // [doc:start] actionDown - Down
    public function actionDown()
    {
        $applied = array_keys($this->applied());
        if (!$applied) {
            $this->stdout("nothing to revert\n");
            return ExitCode::OK;
        }
        $name = end($applied);
        $scripts = $this->scripts();
        if (empty($scripts[$name]['down'])) {
            $this->stderr("no down script for $name\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
        $this->stdout("will revert $name\n");
        if (!$this->yes) {
            $this->stdout("dry run - add --yes to revert (backup first)\n");
            return ExitCode::OK;
        }
        $this->executeFile($scripts[$name]['down']);
        Yii::$app->db->createCommand()->delete('db_migration_log', ['name' => $name])->execute();
        $this->stdout("reverted\n");

        return ExitCode::OK;
    }
    // [doc:end] actionDown

    private function scripts()
    {
        $found = [];
        foreach (glob(Yii::getAlias('@app/database/migrations') . '/*.sql') as $file) {
            if (preg_match('/^(.+)\.(up|down)\.sql$/', basename($file), $m)) {
                $found[$m[1]][$m[2]] = $file;
            }
        }
        ksort($found);

        return array_filter($found, function ($f) {
            return isset($f['up']);
        });
    }

    private function applied()
    {
        Yii::$app->db->createCommand(
            'CREATE TABLE IF NOT EXISTS db_migration_log (name varchar(120) NOT NULL PRIMARY KEY, applied_on datetime NOT NULL) ENGINE=InnoDB'
        )->execute();

        return array_flip(Yii::$app->db->createCommand('SELECT name FROM db_migration_log ORDER BY applied_on, name')->queryColumn());
    }

    private function executeFile($file)
    {
        $sql = preg_replace('/^\s*--.*$/m', '', (string) file_get_contents($file));
        foreach (preg_split('/;\s*(\r?\n|$)/', $sql) as $statement) {
            $statement = trim($statement);
            if ($statement !== '') {
                Yii::$app->db->createCommand($statement)->execute();
            }
        }
    }
}