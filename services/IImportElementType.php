<?php
namespace Craft;

/**
 * Import Element Type Interface
 *
 * Interface for Importing Element Types
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://github.com/boboldehampsink
 * @package   craft.plugins.import
 */
interface IImportElementType
{
    /**
     * Return groups
     */
    public function getGroups();

    /**
     * Return element model with group
     * @param array $settings
     */
    public function setModel(array $settings);

    /**
     * Set element criteria
     * @param arrya $settings
     */
    public function setCriteria(array $settings);

    /**
     * Delete elements
     * @return boolean
     */
    public function delete(array $elements);

    /**
     * Prepare reserved ElementModel values
     * @param  array            &$fields
     * @param  BaseElementModel $element
     */
    public function prepForElementModel(array &$fields, BaseElementModel $element);

    /**
     * Save an element
     * @param  BaseElementModel &$element
     * @param  array            $settings
     */
    public function save(BaseElementModel &$element, array $settings);

    /**
     * Executes after saving an element
     * @param  array         $fields
     */
    public function callback(array $fields, BaseElementModel $element);
}
