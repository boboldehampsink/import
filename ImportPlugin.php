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
        return '0.4';
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
    
    // Register ImportOperation hook
    function registerImportOperation(&$data, $handle)
    {
        return craft()->import->prepForFieldType($data, $handle);
    
    }
    
}