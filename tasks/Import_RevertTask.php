<?php
namespace Craft;

class Import_RevertTask extends BaseTask 
{

    protected function defineSettings() 
    {
    
        return array(
            'entries' => AttributeType::Mixed
        );
    
    }

    public function getDescription() 
    {
    
        return Craft::t('Revert Import');
    
    }
    
    public function getTotalSteps() 
    {
    
        // Delete element template caches before importing
        craft()->templateCache->deleteCachesByElementType(ElementType::Entry);
    
        // Take a step for every row
        return count($this->getSettings()->entries);
    
    }
    
    public function runStep($step)
    {
    
        // Get settings
        $settings = $this->getSettings();
        
        // Check if entry exists
        if(isset($settings->entries[$step])) {
        
            // Get version id
            $versionId = $settings->entries[$step]['versionId'];
        
            // Get version
            $version = craft()->entryRevisions->getVersionById($versionId);
            
            // Revert to version
            craft()->entryRevisions->revertEntryToVersion($version);
    
        }
    
        return true;
    
    }

}