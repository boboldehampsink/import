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
        foreach ($fields as $handle => $value) {
            switch ($handle) {
                case Import_ElementModel::HandleLocale:
                    $element->localeEnabled = true;
                case Import_ElementModel::HandleId;
                    $element->$handle = $value;
                    break;
                case Import_ElementModel::HandleSlug:
                    $element->$handle = ElementHelper::createSlug($value);
                    break;
                case Import_ElementModel::HandleTitle:
                    $element->getContent()->$handle = $value;
                    break;
                default:
                    continue 2;
            }
            unset($fields[$handle]);
        }

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
        $parent = Import_ElementModel::HandleParent;
        $ancestors = Import_ElementModel::HandleAncestors;
        $parentCategory = null;

        if (isset($fields[$parent])) {
            $parentCategory = $this->prepareParentForElement($fields[$parent], $element->groupId);
            unset($fields[$parent]);
        } elseif (isset($fields[$ancestors])) {
            $parentCategory = $this->prepareAncestorsForElement($element, $fields[$ancestors]);
            unset($fields[$ancestors]);
        }

        if ($parentCategory) {
            $categoryGroup = craft()->categories->getGroupById($element->groupId);
            craft()->structures->append($categoryGroup->structureId, $element, $parentCategory, 'auto');
        }
    }

    /**
     * @param $data
     * @param $groupId
     *
     * @return null|CategoryModel
     */
    private function prepareParentForElement($data, $groupId)
    {
        $parentCategory = null;
        $data = $this->freshenString($data);

        // Don't connect empty fields
        if (!empty($data)) {

            // Find matching element
            $criteria = craft()->elements->getCriteria(ElementType::Category);
            $criteria->groupId = $groupId;

            // Exact match
            $criteria->search = '"'.$data.'"';
            $parentCategory = $criteria->first();
        }

        return $parentCategory;
    }

    /**
     * @param BaseElementModel $element
     * @param $data
     *
     * @return null|CategoryModel
     */
    private function prepareAncestorsForElement(BaseElementModel $element, $data)
    {
        $parentCategory = null;
        $data = $this->freshenString($data);

        // Don't connect empty fields
        if (!empty($data)) {

            // This we append before the slugified path
            $categoryUrl = str_replace('{slug}', '', $element->getUrlFormat());

            // Find matching element by URI (dirty, not all categories have URI's)
            $criteria = craft()->elements->getCriteria(ElementType::Category);
            $criteria->groupId = $element->groupId;
            $criteria->uri = $categoryUrl.craft()->import->slugify($data);
            $criteria->limit = 1;

            $parentCategory = $criteria->first();
        }

        return $parentCategory;
    }

    /**
     * @param $data
     *
     * @return mixed|string
     */
    private function freshenString($data)
    {
        $data = str_replace("\n", '', $data);
        $data = str_replace("\r", '', $data);
        $data = trim($data);

        return $data;
    }
}
