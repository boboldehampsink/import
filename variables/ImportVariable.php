<?php 
namespace Craft;

class ImportVariable 
{

    public function history() 
    {
    
        return craft()->import_history->show();
    
    }
    
    public function log($history) 
    {
    
        return craft()->import_history->showLog($history);
    
    }

}