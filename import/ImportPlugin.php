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
        return '0.7.0';
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
    
    // Register CP routes
    function registerCpRoutes() 
    {
        return array(
            'import/(?P<historyId>\d+)' => 'import/_history'
        );
    
    }
    
    // Register ImportOperation hook
    function registerImportOperation(&$data, $handle)
    {
        return craft()->import->prepForFieldType($data, $handle);
    }
    
    // Check if the plugin meets the requirements, else uninstall again
    function onAfterInstall() 
    {
    
        // Minimum build is 2554
        $minBuild = '2554';
        
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