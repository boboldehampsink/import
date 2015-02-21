<?php
namespace Craft;

class ImportTask extends BaseTask
{
    protected $backupFile = false;

    protected function defineSettings()
    {
        return array(
            'file'        => AttributeType::Name,
            'rows'        => AttributeType::Number,
            'map'         => AttributeType::Mixed,
            'unique'      => AttributeType::Mixed,
            'type'        => AttributeType::String,
            'elementvars' => AttributeType::Mixed,
            'behavior'    => AttributeType::Name,
            'email'       => AttributeType::Email,
            'backup'      => AttributeType::Bool,
            'history'     => AttributeType::Number,
        );
    }

    public function getDescription()
    {
        return Craft::t('Import');
    }

    public function getTotalSteps()
    {

        // Get settings
        $settings = $this->getSettings();

        // Delete element template caches before importing
        craft()->templateCache->deleteCachesByElementType($settings->type);

        // Take a step for every row
        return $settings->rows;
    }

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
