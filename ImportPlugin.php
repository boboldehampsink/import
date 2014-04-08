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
    
    // Register import summary email message
    function registerEmailMessages()
    {
        return array(
            'importSummary'
        );
    }
    
    // Register ImportOperation
    // Create a hook and call it from service
    function registerImportOperation(&$data, $handle)
    {
        return craft()->import->prepForFieldType($data, $handle);
    
    }
    
}