<?php

namespace Craft;

/**
 * Import Revert Task.
 *
 * Contains logic for importing
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 */
class ImportTask extends BaseTask
{
    /**
     * Backup file name.
     *
     * @var bool
     */
    protected $backupFile = false;

    /**
     * Define settings.
     *
     * @return array
     */
    protected function defineSettings()
    {
        return array(
            'user' => AttributeType::Number,
            'file' => AttributeType::Name,
            'rows' => AttributeType::Number,
            'map' => AttributeType::Mixed,
            'unique' => AttributeType::Mixed,
            'type' => AttributeType::String,
            'elementvars' => array(AttributeType::Mixed, 'default' => array()),
            'behavior' => array(AttributeType::Name, 'default' => ImportModel::BehaviorAppend),
            'email' => AttributeType::Email,
            'backup' => array(AttributeType::Bool, 'default' => false),
            'history' => AttributeType::Number,
        );
    }

    /**
     * Return description.
     *
     * @return string
     */
    public function getDescription()
    {
        return Craft::t('Import');
    }

    /**
     * Return total steps.
     *
     * @return int
     */
    public function getTotalSteps()
    {
        // Get settings
        $settings = $this->getSettings();

        // Delete element template caches before importing
        craft()->templateCache->deleteCachesByElementType($settings->type);

        // Take a step for every row
        return $settings->rows;
    }

    /**
     * Run step.
     *
     * @param int $step
     *
     * @return bool
     */
    public function runStep($step)
    {
        // Get settings
        $settings = $this->getSettings();

        // Backup?
        if ($settings->backup && !$step) {

            // Do the backup
            $backup = new DbBackup();
            $this->backupFile = $backup->run();
        }

        // Open file
        $data = craft()->import->data($settings->file);

        // On start
        if (!$step) {

            // Fire an "onImportStart" event
            $event = new Event($this, array('settings' => $settings));
            craft()->import->onImportStart($event);
        }

        // Check if row exists
        if (isset($data[$step])) {

            // Import row
            craft()->import->row($step, $data[$step], $settings);
        }

        // When finished
        if ($step == ($settings->rows - 1)) {

            // Finish
            craft()->import->finish($settings, $this->backupFile);

            // Fire an "onImportFinish" event
            $event = new Event($this, array('settings' => $settings));
            craft()->import->onImportFinish($event);
        }

        return true;
    }
}
