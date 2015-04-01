<?php

namespace Craft;

/**
 * Import Element Type Interface.
 *
 * Interface for Importing Element Types
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 */
interface IImportElementType
{
    /**
     * Return import template.
     *
     * @return string
     */
    public function getTemplate();

    /**
     * Return groups.
     */
    public function getGroups();

    /**
     * Return element model with group.
     *
     * @param array|object $settings
     */
    public function setModel($settings);

    /**
     * Set element criteria.
     *
     * @param array|object $settings
     */
    public function setCriteria($settings);

    /**
     * Delete elements.
     *
     * @return bool
     */
    public function delete(array $elements);

    /**
     * Prepare reserved ElementModel values.
     *
     * @param array            &$fields
     * @param BaseElementModel $element
     */
    public function prepForElementModel(array &$fields, BaseElementModel $element);

    /**
     * Save an element.
     *
     * @param BaseElementModel &$element
     * @param array|object     $settings
     */
    public function save(BaseElementModel &$element, $settings);

    /**
     * Executes after saving an element.
     *
     * @param array $fields
     */
    public function callback(array $fields, BaseElementModel $element);
}
