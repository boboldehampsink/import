<?php

namespace Craft;

/**
 * Import Entry Service.
 *
 * Contains logic for importing entries
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 */
class Import_EntryService extends BaseApplicationComponent implements IImportElementType
{
    /**
     * Return import template.
     *
     * @return string
     */
    public function getTemplate()
    {
        return 'import/types/entry/_upload';
    }

    /**
     * Return groups.
     *
     * @return array
     */
    public function getGroups()
    {
        // Get editable sections for user
        $editable = craft()->sections->getEditableSections();

        // Get sections but not singles
        $sections = array();
        foreach ($editable as $section) {
            if ($section->type != SectionType::Single) {
                $sections[] = $section;
            }
        }

        return $sections;
    }

    /**
     * Return entry model with group.
     *
     * @param array|object $settings
     *
     * @return EntryModel
     */
    public function setModel($settings)
    {
        // Set up new entry model
        $element = new EntryModel();
        $element->sectionId = $settings['elementvars']['section'];
        $element->typeId = $settings['elementvars']['entrytype'];

        return $element;
    }

    /**
     * Set entry criteria.
     *
     * @param array|object $settings
     *
     * @return ElementCriteriaModel
     */
    public function setCriteria($settings)
    {
        // Match with current data
        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        $criteria->limit = null;
        $criteria->status = isset($settings['map']['status']) ? $settings['map']['status'] : null;

        // Look in same section when replacing
        $criteria->sectionId = $settings['elementvars']['section'];
        $criteria->type = $settings['elementvars']['entrytype'];

        return $criteria;
    }

    /**
     * Delete entries.
     *
     * @param array $elements
     *
     * @return bool
     */
    public function delete(array $elements)
    {
        // Delete entry
        return craft()->entries->deleteEntry($elements);
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

        // Set author
        $author = Import_ElementModel::HandleAuthor;
        if (isset($fields[$author])) {
            $user = craft()->users->getUserByUsernameOrEmail($fields[$author]);
            $element->$author = (is_numeric($fields[$author]) ? $fields[$author] : ($user ? $user->id : 1));
            unset($fields[$author]);
        } else {
            $user = craft()->userSession->getUser();
            $element->$author = ($element->$author ? $element->$author : ($user ? $user->id : 1));
        }

        // Set slug
        $slug = Import_ElementModel::HandleSlug;
        if (isset($fields[$slug])) {
            $element->$slug = ElementHelper::createSlug($fields[$slug]);
            unset($fields[$slug]);
        }

        // Set postdate
        $postDate = Import_ElementModel::HandlePostDate;
        if (isset($fields[$postDate])) {
            $element->$postDate = DateTime::createFromString($fields[$postDate], craft()->timezone);
            unset($fields[$postDate]);
        }

        // Set expiry date
        $expiryDate = Import_ElementModel::HandleExpiryDate;
        if (isset($fields[$expiryDate])) {
            $element->$expiryDate = DateTime::createFromString($fields[$expiryDate], craft()->timezone);
            unset($fields[$expiryDate]);
        }

        // Set enabled
        $enabled = Import_ElementModel::HandleEnabled;
        if (isset($fields[$enabled])) {
            $element->$enabled = (bool) $fields[$enabled];
            unset($fields[$enabled]);
        }

        // Set title
        $title = Import_ElementModel::HandleTitle;
        if (isset($fields[$title])) {
            $element->getContent()->$title = $fields[$title];
            unset($fields[$title]);
        }

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
                $criteria = craft()->elements->getCriteria(ElementType::Entry);
                $criteria->sectionId = $element->sectionId;

                // Exact match
                $criteria->search = '"'.$data.'"';

                // Return the first found element for connecting
                if ($criteria->total()) {
                    $element->$parent = $criteria->first()->id;
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

                // Get section data
                $section = new SectionModel();
                $section->id = $element->sectionId;

                // This we append before the slugified path
                $sectionUrl = str_replace('{slug}', '', $section->getUrlFormat());

                // Find matching element by URI (dirty, not all structures have URI's)
                $criteria = craft()->elements->getCriteria(ElementType::Entry);
                $criteria->sectionId = $element->sectionId;
                $criteria->uri = $sectionUrl.craft()->import->slugify($data);
                $criteria->limit = 1;

                // Return the first found element for connecting
                if ($criteria->total()) {
                    $element->$parent = $criteria->first()->id;
                }
            }

            unset($fields[$ancestors]);
        }

        // Return element
        return $element;
    }

    /**
     * Save an entry.
     *
     * @param BaseElementModel &$element
     * @param array|object     $settings
     *
     * @return bool
     */
    public function save(BaseElementModel &$element, $settings)
    {
        // Save user
        if (craft()->entries->saveEntry($element)) {

            // If entry revisions are supported
            if (craft()->getEdition() == Craft::Pro) {

                // Log element id's when successful
                craft()->import_history->version($settings['history'], $element->id);
            }

            return true;
        }

        return false;
    }

    /**
     * Executes after saving an entry.
     *
     * @param array            $fields
     * @param BaseElementModel $element
     */
    public function callback(array $fields, BaseElementModel $element)
    {
        // No callback for entries
    }
}
