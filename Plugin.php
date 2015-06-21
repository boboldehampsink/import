<?php

namespace craft\plugins\import;

use Craft;

/**
 * Import Plugin.
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 */
class Plugin extends \craft\app\base\Plugin
{
    /**
     * Return if plugin has cp section.
     *
     * @return bool
     */
    public static function hasCpSection()
    {
        return true;
    }

    /**
     * Register twig variable location.
     *
     * @return string
     */
    public function getVariableDefinition()
    {
        return 'craft\plugins\import\web\twig\variables\Import';
    }

    /**
     * Register CP routes.
     *
     * @return array
     */
    public function registerCpRoutes()
    {
        return array(
            'import/history/(?P<historyId>\d+)' => 'import/history/_log',
        );
    }

    /**
     * Register permissions.
     *
     * @return array
     */
    public function registerUserPermissions()
    {
        return array(
            // Behavior permissions
            ImportModel::BehaviorAppend  => array('label' => Craft::t('Append data')),
            ImportModel::BehaviorReplace => array('label' => Craft::t('Replace data')),
            ImportModel::BehaviorDelete  => array('label' => Craft::t('Delete data')),
            // Backup permissions
            ImportModel::Backup          => array('label' => Craft::t('Backup Database')),
        );
    }

    /**
     * Register ImportOperation hook.
     *
     * @param        &$data
     * @param string $handle
     *
     * @return string
     */
    public function registerImportOperation(&$data, $handle)
    {
        return craft()->import->prepForFieldType($data, $handle);
    }

    /**
     * Check if the plugin meets the requirements, else uninstall again.
     */
    public function onAfterInstall()
    {

        // Minimum build is 2615
        $minBuild = '2615';

        // If your build is lower
        if (craft()->getBuild() < $minBuild) {

            // First disable plugin
            // With this we force Craft to look up the plugin's ID, which isn't cached at this moment yet
            // Without this we get a fatal error
            craft()->plugins->disablePlugin($this->getClassHandle());

            // Uninstall plugin
            craft()->plugins->uninstallPlugin($this->getClassHandle());

            // Show error message
            craft()->userSession->setError(Craft::t('{plugin} only works on Craft build {build} or higher', array(
                'plugin' => $this->getName(),
                'build' => $minBuild,
            )));
        }
    }
}
