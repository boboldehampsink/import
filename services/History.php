<?php

namespace craft\plugins\import\services;

use Craft;
use yii\base\Component;

/**
 * Import History Service.
 *
 * Contains logic for showing import history
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 */
class History extends Component
{
    /**
     * Show all log entries.
     *
     * @return array
     */
    public function show()
    {
        return \craft\plugins\import\records\History::find();
    }

    /**
     * Show a specific log item.
     *
     * @param int $history
     *
     * @return array
     */
    public function showLog($history)
    {
        // Get logs
        $logs = \craft\plugins\import\records\History::find()->where(array(':historyId' => $history));

        // Get errors
        $errors = array();
        foreach ($logs as $log) {
            $errors[$log['line']] = $log['errors'];
        }

        // Get total rows
        $model = \craft\plugins\import\records\History::findByPk($history);

        $total = array();
        if ($model) {
            $rows = $model->rows;

            // Make "total" list
            for ($i = 2; $i <= ($rows + 1); $i++) {
                $total[$i] = isset($errors[$i]) ? $errors[$i] : array(Craft::t('None'));
            }
        }

        return $total;
    }

    /**
     * Start logging.
     *
     * @param array|object $settings
     *
     * @return int
     */
    public function start($settings)
    {
        $history              = new \craft\plugins\import\records\History();
        $history->userId      = craft()->userSession->getUser()->id;
        $history->type        = $settings['type'];
        $history->file        = basename($settings['file']);
        $history->rows        = $settings['rows'];
        $history->behavior    = $settings['behavior'];
        $history->status      = \craft\plugins\import\models\History::StatusStarted;

        $history->save(false);

        return $history->id;
    }

    /**
     * Add to log.
     *
     * @param int   $history
     * @param int   $line
     * @param array $errors
     *
     * @return array
     */
    public function log($history, $line, array $errors)
    {
        if (Import_HistoryRecord::model()->findById($history)) {
            $log = new Import_LogRecord();
            $log->historyId = $history;
            $log->line = $line + 2;
            $log->errors = $errors;

            $log->save(false);
        }

        return $errors;
    }

    /**
     * Stop logging.
     *
     * @param int    $history
     * @param string $status
     */
    public function end($history, $status)
    {
        $history = Import_HistoryRecord::model()->findById($history);
        $history->status = $status;

        $history->save(false);
    }

    /**
     * Clear history.
     *
     * @param int $history
     */
    public function clear($history)
    {
        // TODO
    }

    /**
     * Save entry version.
     *
     * @param int $history
     * @param int $entry
     */
    public function version($history, $entry)
    {

        // Get previous version
        $version = end(craft()->entryRevisions->getVersionsByEntryId($entry, false, 2));

        // Save
        $log = new Import_EntriesRecord();
        $log->historyId = $history;
        $log->entryId = $entry;
        $log->versionId = $version ? $version->versionId : null;

        $log->save(false);
    }
}
