<?php
namespace Craft;

/**
 * Import Variable
 *
 * Injects logics into the templates
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://github.com/boboldehampsink
 * @package   craft.plugins.import
 */
class ImportVariable
{
    /**
     * Get groups for service
     * @param  string $elementType
     * @return array|boolean
     */
    public function getGroups($elementType)
    {
        // Get from right elementType
        $service = 'import_'.strtolower($elementType);

        // Check if elementtype can be imported
        if (isset(craft()->$service)) {

            // Return "groups" (section, groups, etc.)
            return craft()->$service->getGroups();
        }

        return false;
    }

    /**
     * Show history overview
     * @return array
     */
    public function history()
    {
        // Return all history
        return craft()->import_history->show();
    }

    /**
     * Show history detail
     * @param  int $history
     * @return array
     */
    public function log($history)
    {
        // Return the log from a certain history
        return craft()->import_history->showLog($history);
    }
}
