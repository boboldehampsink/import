<?php
namespace Craft;

class ImportTask extends BaseTask {

    protected function defineSettings() {
    
        return array(
            'file'      => AttributeType::Name,
            'rows'      => AttributeType::Number,
            'map'       => AttributeType::Mixed,
            'unique'    => AttributeType::Mixed,
            'section'   => AttributeType::Number,
            'entrytype' => AttributeType::Number,
            'behavior'  => AttributeType::Name
        );
    
    }

    public function getDescription() {
    
        return Craft::t('Import');
    
    }
    
    public function getTotalSteps() {
    
        // Get total rows
        $rows = $this->getSettings()->rows;
    
        // Write start of log
        ImportPlugin::log('Starting import of ' . $rows . ' rows', LogLevel::Profile);
    
        // Delete element template caches before importing
        craft()->templateCache->deleteCachesByElementType(ElementType::Entry);
    
        // Take a step for every row
        return $rows;
    
    }
    
    public function runStep($step) {
    
        // Get settings
        $settings = $this->getSettings();
    
        // Open file
        $data = craft()->import->data($settings->file);
        
        if(isset($data[$step])) {
                
            // Import row
            craft()->import->row($step, $data[$step], $settings);
            
        }
        
        // When finished
        if($step == ($settings['rows']-1)) {
        
            // Write end of log
            ImportPlugin::log('End of import', LogLevel::Profile);
        
        }
    
        return true;
    
    }

}