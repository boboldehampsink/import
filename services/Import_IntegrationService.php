<?php

namespace Craft;

/**
 * Import Integration Service.
 *
 * Allows 3rd-party plugins to integrate with Import
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 */
class Import_IntegrationService extends BaseApplicationComponent
{
    public $customOptionPaths = array();

    /**
     * Init
     */
    public function init()
    {
        // Call hook for all plugins
        $responses = craft()->plugins->call('registerImportOptionPaths');

        // Loop through responses from each plugin
        foreach ($responses as $customPaths) {

            // Append custom paths to master list
            $this->customOptionPaths = array_merge($this->customOptionPaths, $customPaths);
        }
    }

    /**
     * Example usage of "registerImportOptionPaths" hook
     *
     * @return array of template paths mapped to fieldtypes
     */
    /*
    public function registerImportOptionPaths()
    {
        return array(
            'MyPlugin_MyFieldType' => 'path/to/option/template',
        );
    }
    */
}
