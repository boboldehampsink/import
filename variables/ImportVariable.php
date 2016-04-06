<?php

namespace Craft;

/**
 * Import Variable.
 *
 * Injects logics into the templates
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 */
class ImportVariable
{
    /**
     * Get groups for service.
     *
     * @param string $elementType
     *
     * @return array|bool
     */
    public function getGroups($elementType)
    {
        // Check if elementtype can be imported
        if ($service = craft()->import->getService($elementType)) {

            // Return "groups" (section, groups, etc.)
            return $service->getGroups();
        }

        return false;
    }

    /**
     * Get template for service.
     *
     * @param string $elementType
     *
     * @return array|bool
     */
    public function getTemplate($elementType)
    {
        // Check if elementtype can be imported
        if ($service = craft()->import->getService($elementType)) {

            // Return template
            return $service->getTemplate();
        }

        return false;
    }

    /**
     * Get viewable asset sources.
     *
     * @return array
     */
    public function getAssetSources()
    {
        return craft()->assetSources->getViewableSources();
    }

    /**
     * Show history overview.
     *
     * @return array
     */
    public function history()
    {
        // Return all history
        return craft()->import_history->show();
    }

    /**
     * Show history detail.
     *
     * @param int $history
     *
     * @return array
     */
    public function log($history)
    {
        // Return the log from a certain history
        return craft()->import_history->showLog($history);
    }

    /**
     * Get path to fieldtype's custom <option> template.
     *
     * @param string $fieldHandle
     *
     * @return string
     */
    public function customOption($fieldHandle)
    {
        // Return custom <option> for template
        return craft()->import->getCustomOption($fieldHandle);
    }
}
