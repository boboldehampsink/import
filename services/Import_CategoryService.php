<?php

namespace Craft;

/**
 * Import Category Service.
 *
 * Contains logic for importing categories
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 */
class Import_CategoryService extends BaseApplicationComponent implements IImportElementType
{
    /**
     * Return import template.
     *
     * @return string
     */
    public function getTemplate()
    {
        return 'import/types/category/_upload';
    }

    /**
     * Return groups.
     *
     * @return array
     */
    public function getGroups()
    {
        // Return editable groups for user
        return craft()->categories->getEditableGroups();
    }

    /**
     * Return category model with group.
     *
     * @param array|object $settings
     *
     * @return CategoryModel
     */
    public function setModel($settings)
    {
        // Set up new category model
        $element = new CategoryModel();
        $element->groupId = $settings['elementvars']['group'];

        return $element;
    }

    /**
     * Set category criteria.
     *
     * @param array|object $settings
     *
     * @return ElementCriteriaModel
     */
    public function setCriteria($settings)
    {

        // Match with current data
        $criteria = craft()->elements->getCriteria(ElementType::Category);
        $criteria->limit = null;
        $criteria->status = isset($settings['map']['status']) ? $settings['map']['status'] : null;

        // Look in same group when replacing
        $criteria->groupId = $settings['elementvars']['group'];

        return $criteria;
    }

    /**
     * Delete categories.
     *
     * @param array $elements
     *
     * @return bool
     */
    public function delete(array $elements)
    {
        // Delete categories
        return craft()->categories->deleteCategory($elements);
    }

    /**
     * Prepare reserved ElementModel values.
     *
     * @param array            &$fields
     * @param BaseElementModel $element
     *
     * @return BaseElementModel
     */
    public function prepForElementModel(array &$fields, BaseElementModel $element)
    {
        // Set ID
        $id = Import_ElementModel::HandleId;
        if (isset($fields[$id])) {
            $element->$id = $fields[$id];
            unset($fields[$id]);
        }

        // Set locale
        $locale = Import_ElementModel::HandleLocale;
        if (isset($fields[$locale])) {
            $element->$locale = $fields[$locale];
            $element->localeEnabled = true;
            unset($fields[$locale]);
        }

        // Set slug
        $slug = Import_ElementModel::HandleSlug;
        if (isset($fields[$slug])) {
            $element->$slug = ElementHelper::createSlug($fields[$slug]);
            unset($fields[$slug]);
        }

        // Set title
        $title = Import_ElementModel::HandleTitle;
        if (isset($fields[$title])) {
            $element->getContent()->$title = $fields[$title];
            unset($fields[$title]);
        }

        // Return element
        return $element;
    }

    /**
     * Save a category.
     *
     * @param BaseElementModel &$element
     * @param array|object     $settings
     *
     * @return bool
     */
    public function save(BaseElementModel &$element, $settings)
    {
        // Save category
        return craft()->categories->saveCategory($element);
    }

    /**
     * Executes after saving a category.
     *
     * @param array            $fields
     * @param BaseElementModel $element
     */
    public function callback(array $fields, BaseElementModel $element)
    {
        // Set parent or ancestors
        $parent = Import_ElementModel::HandleParent;
        $ancestors = Import_ElementModel::HandleAncestors;

        if (isset($fields[$parent])) {

            // Get data
            $data = $fields[$parent];

            // Fresh up $data
            $data = str_replace("\n", '', $data);
            $data = str_replace("\r", '', $data);
            $data = trim($data);

            // Don't connect empty fields
            if (!empty($data)) {

                // Find matching element
                $criteria = craft()->elements->getCriteria(ElementType::Category);
                $criteria->groupId = $element->groupId;

                // Exact match
                $criteria->search = '"'.$data.'"';

                // Return the first found element for connecting
                if ($criteria->total()) {

                    // Get category group
                    $categoryGroup = craft()->categories->getGroupById($element->groupId);

                    // Set structure
                    craft()->structures->append($categoryGroup->structureId, $element, $criteria->first(), 'auto');
                }
            }

            unset($fields[$parent]);
        } elseif (isset($fields[$ancestors])) {

            // Get data
            $data = $fields[$ancestors];

            // Fresh up $data
            $data = str_replace("\n", '', $data);
            $data = str_replace("\r", '', $data);
            $data = trim($data);

            // Don't connect empty fields
            if (!empty($data)) {

                // This we append before the slugified path
                $categoryUrl = str_replace('{slug}', '', $element->getUrlFormat());

                // Find matching element by URI (dirty, not all categories have URI's)
                $criteria = craft()->elements->getCriteria(ElementType::Category);
                $criteria->groupId = $element->groupId;
                $criteria->uri = $categoryUrl.craft()->import->slugify($data);
                $criteria->limit = 1;

                // Return the first found element for connecting
                if ($criteria->total()) {

                    // Get category group
                    $categoryGroup = craft()->categories->getGroupById($element->groupId);

                    // Set structure
                    craft()->structures->append($categoryGroup->structureId, $element, $criteria->first(), 'auto');
                }
            }

            unset($fields[$ancestors]);
        }
    }
}
