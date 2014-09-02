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
    
        // Delete element template caches before importing
        craft()->templateCache->deleteCachesByElementType(ElementType::Entry);
    
        // Take a step for every row
        return $this->getSettings()->rows;
    
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
            
            // Run custom hook on finish
            craft()->plugins->call('registerImportFinish', array($settings));
        
        }
    
        return true;
    
    }

}