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
        return '0.6';
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
    
    // Check if the plugin meets the requirements, else uninstall again
    function onAfterInstall() {
    
        // Minimum build is 2535
        $minBuild = '2535';
        
        // If your build is lower
        if(craft()->getBuild() < $minBuild) {
        
            // First disable plugin
            // With this we force Craft to look up the plugin's ID, which isn't cached at this moment yet
            // Without this we get a fatal error
            craft()->plugins->disablePlugin($this->getClassHandle());
    
            // Uninstall plugin
            craft()->plugins->uninstallPlugin($this->getClassHandle());
            
            // Show error message
            craft()->userSession->setError(Craft::t('{plugin} only works on Craft build {build} or higher', array(
                'plugin' => $this->getName(),
                'build' => $minBuild
            )));
        
        }
    
    }
    
}