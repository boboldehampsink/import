<?php 
namespace Craft;

class ImportVariable 
{

    public function getGroups($elementType) 
    {
    
        // Get from right elementType
        $service = 'import_' . strtolower($elementType);
        
        // Check if elementtype can be imported
        if(isset(craft()->$service)) {
    
            // Return "groups" (section, groups, etc.)
            return craft()->$service->getGroups();
            
        } 
        
        return false;
    
    }

    public function history() 
    {
    
        // Return all history
        return craft()->import_history->show();
    
    }
    
    public function log($history) 
    {
    
        // Return the log from a certain history
        return craft()->import_history->showLog($history);
    
    }

}