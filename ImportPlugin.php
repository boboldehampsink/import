<?php
namespace Craft;

class ImportPlugin extends BasePlugin
{
    function getName()
    {
        return Craft::t('Import');
    }

    function getVersion()
    {
        return '0.3.1';
    }

    function getDeveloper()
    {
        return 'Bob Olde Hampsink';
    }

    function getDeveloperUrl()
    {
        return 'http://www.itmundi.nl';
    }
    
    function hasCpSection()
    {
        return true;
    }
    
    // Create a hook and call it from service
    function registerFieldTypeOperation(&$data, $handle) {
    
        return craft()->import->prepForFieldType($data, $handle);
    
    }
    
}