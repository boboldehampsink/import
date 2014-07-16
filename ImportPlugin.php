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
        return '0.7.1';
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
    
    // See if there are templates to be overwritten
    function init()
    {
        
        // Only check in CP
        if(craft()->request->isCpRequest()) {
        
            $segments = craft()->request->segments;
            
            // Only check in import plugin
            if(isset($segments[0]) && $segments[0] == 'import') {
            
                // Only check on upload tempalte
                if(isset($segments[1]) && $segments[1] == 'upload' && $_SERVER['REQUEST_METHOD'] != 'POST') {
                
                    // Render template (by hook, so you can edit the template)
                    $templates = craft()->plugins->call('registerImportTemplate', array('upload'));
                                
                    // Check if there's a custom template
                    foreach($templates as $plugin => $template) {
                        
                        if($template) {
                    
                            // If so, return that template
                            BaseController::renderTemplate($template);
                            
                        }
                    
                    }
                
                }
            
            }
        
        }
        
    }
    
    // Register CP routes
    function registerCpRoutes() 
    {
        return array(
            'import/(?P<historyId>\d+)' => 'import/_history'
        );
    }
    
    // Register permissions
    function registerUserPermissions()
    {
        return array(
            // Behavior permissions
            ImportModel::BehaviorAppend => array('label' => Craft::t('Append data')),
            ImportModel::BehaviorReplace => array('label' => Craft::t('Replace data')),
            ImportModel::BehaviorDelete => array('label' => Craft::t('Delete data'))
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