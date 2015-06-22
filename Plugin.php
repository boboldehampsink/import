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
        return [
            'import/history/(?P<historyId>\d+)' => 'import/history/_log',
        ];
    }

    /**
     * Register permissions.
     *
     * @return array
     */
    public function registerUserPermissions()
    {
        return [
            // Behavior permissions
            \craft\plugins\import\models\Import::BehaviorAppend  => array('label' => Craft::t('Append data')),
            \craft\plugins\import\models\Import::BehaviorReplace => array('label' => Craft::t('Replace data')),
            \craft\plugins\import\models\Import::BehaviorDelete  => array('label' => Craft::t('Delete data')),
            // Backup permissions
            \craft\plugins\import\models\Import::Backup          => array('label' => Craft::t('Backup Database')),
        ];
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
        return Craft::$app->plugins->getPlugin('import')->import->prepForFieldType($data, $handle);
    }
}
