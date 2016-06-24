<?php

namespace Craft;

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
class Import_HistoryService extends BaseApplicationComponent
{
    /**
     * Show all log entries.
     *
     * @return array
     */
    public function show()
    {
        // Set criteria
        $criteria = new \CDbCriteria();
        $criteria->order = 'id desc';

        return $this->findAllHistories($criteria);
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
        // Set criteria
        $criteria = new \CDbCriteria();
        $criteria->condition = 'historyId = :history_id';
        $criteria->params = array(
            ':history_id' => $history,
        );

        // Get errors
        $errors = array();
        $logs = $this->findAllLogs($criteria);
        foreach ($logs as $log) {
            $errors[$log['line']] = $log['errors'];
        }

        // Get total rows
        $model = $this->findHistoryById($history);

        $total = array();

        if ($model) {
            $rows = $model->rows;

            // Make "total" list
            for ($i = 2; $i <= ($rows + 1); ++$i) {
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
        $history = $this->getNewImportHistoryRecord();
        $history->userId = $settings['user'];
        $history->type = $settings['type'];
        $history->file = $settings['file'];
        $history->rows = $settings['rows'];
        $history->behavior = $settings['behavior'];
        $history->status = ImportModel::StatusStarted;

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
        if ($this->findHistoryById($history)) {
            $log = $this->getNewImportLogRecord();
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
        $history = $this->findHistoryById($history);
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
        $entryRevisions = craft()->entryRevisions->getVersionsByEntryId($entry, false, 2);
        $version = end($entryRevisions);

        // Save
        $log = $this->getNewImportEntriesRecord();
        $log->historyId = $history;
        $log->entryId = $entry;
        $log->versionId = $version ? $version->versionId : null;

        $log->save(false);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param \CDbCriteria $criteria
     *
     * @return Import_HistoryRecord[]
     */
    protected function findAllHistories(\CDbCriteria $criteria)
    {
        return Import_HistoryRecord::model()->findAll($criteria);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param \CDbCriteria $criteria
     *
     * @return Import_LogRecord[]
     */
    protected function findAllLogs(\CDbCriteria $criteria)
    {
        return Import_LogRecord::model()->findAll($criteria);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param int $historyId
     *
     * @return Import_HistoryRecord
     */
    protected function findHistoryById($historyId)
    {
        return Import_HistoryRecord::model()->findById($historyId);
    }

    /**
     * @codeCoverageIgnore
     *
     * @return Import_HistoryRecord
     */
    protected function getNewImportHistoryRecord()
    {
        return new Import_HistoryRecord();
    }

    /**
     * @codeCoverageIgnore
     *
     * @return Import_EntriesRecord
     */
    protected function getNewImportEntriesRecord()
    {
        return new Import_EntriesRecord();
    }

    /**
     * @codeCoverageIgnore
     *
     * @return Import_LogRecord
     */
    protected function getNewImportLogRecord()
    {
        return new Import_LogRecord();
    }
}
