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
            'elementtype' => AttributeType::String,
            'section'     => AttributeType::Number,
            'entrytype'   => AttributeType::Number,
            'groups'      => AttributeType::Mixed,
            'behavior'    => AttributeType::Name,
            'email'       => AttributeType::Email,
            'backup'      => AttributeType::Bool,
            'history'     => AttributeType::Number
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
        craft()->templateCache->deleteCachesByElementType($settings->elementtype);
    
        // Take a step for every row
        return $settings->rows;
    
    }
    
    public function runStep($step)
    {
    
        // Get settings
        $settings = $this->getSettings();
        
        // Backup?
        if($settings->backup && !$step) {
        
            // Do the backup
            $backup = new DbBackup();
            $this->backupFile = $backup->run();
        
        }
    
        // Open file
        $data = craft()->import->data($settings->file);
        
        // Check if row exists
        if(isset($data[$step])) {
                
            // Import row
            craft()->import->row($step, $data[$step], $settings);
            
        }
        
        // When finished
        if($step == ($settings->rows - 1)) {
            
            // Finish
            craft()->import->finish($settings, $this->backupFile);
            
            // Fire an "onImportFinish" event
            Craft::import('plugins.import.events.ImportFinishEvent');
            $event = new ImportFinishEvent($this, array('settings' => $settings));
            $this->onImportFinish($event);
        
        }
    
        return true;
    
    }
    
    // Fires an "onImportFinish" event
    public function onImportFinish(ImportFinishEvent $event)
    {
        $this->raiseEvent('onImportFinish', $event);
    }

}