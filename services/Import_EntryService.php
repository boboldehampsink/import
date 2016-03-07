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
        $author = Import_ElementModel::HandleAuthor;
        $parent = Import_ElementModel::HandleParent;
        $checkAncestors = !isset($fields[$parent]);

        foreach ($fields as $handle => $value) {
            switch ($handle) {
                case Import_ElementModel::HandleLocale:
                    $element->localeEnabled = true;
                case Import_ElementModel::HandleId;
                    $element->$handle = $value;
                    break;
                case Import_ElementModel::HandleAuthor;
                    $element->$handle = $this->prepAuthorForElement($value);
                    break;
                case Import_ElementModel::HandleSlug;
                    $element->$handle = ElementHelper::createSlug($value);
                    break;
                case Import_ElementModel::HandlePostDate:
                case Import_ElementModel::HandleExpiryDate;
                    $element->$handle = DateTime::createFromString($value, craft()->timezone);
                    break;
                case Import_ElementModel::HandleEnabled:
                    $element->$handle = (bool) $value;
                    break;
                case Import_ElementModel::HandleTitle:
                    $element->getContent()->$handle = $value;
                    break;
                case Import_ElementModel::HandleParent:
                    $element->$handle = $this->prepareParentForElement($value, $element->sectionId);
                    break;
                case Import_ElementModel::HandleAncestors:
                    if ($checkAncestors) {
                        $element->$parent = $this->prepareAncestorsForElement($value, $element->sectionId);
                    }
                    break;
                default:
                    continue 2;
            }
            unset($fields[$handle]);
        }

        // Set default author if not set
        if (!$element->$author) {
            $user = craft()->userSession->getUser();
            $element->$author = ($element->$author ? $element->$author : ($user ? $user->id : 1));
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
            if ($this->getCraftEdition() == Craft::Pro) {

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

    /**
     * @param string|int $author Author id, username or email
     *
     * @return int authorId
     */
    private function prepAuthorForElement($author)
    {
        if (!is_numeric($author)) {
            $user = craft()->users->getUserByUsernameOrEmail($author);
            $author = $user ? $user->id : 1;
        }

        return $author;
    }

    /**
     * @param string $data
     * @param int    $sectionId
     *
     * @return null|int
     */
    private function prepareParentForElement($data, $sectionId)
    {
        $parentId = null;
        $data = $this->freshenString($data);

        // Don't connect empty fields
        if (!empty($data)) {

            // Find matching element
            $criteria = craft()->elements->getCriteria(ElementType::Entry);
            $criteria->sectionId = $sectionId;

            // Exact match
            $criteria->search = '"'.$data.'"';

            // Return the first found element for connecting
            if ($criteria->total()) {
                $parentId = $criteria->ids()[0];
            }
        }

        return $parentId;
    }

    /**
     * @param string $data
     * @param int    $sectionId
     *
     * @return null|int
     */
    private function prepareAncestorsForElement($data, $sectionId)
    {
        $parentId = null;
        $data = $this->freshenString($data);

        // Don't connect empty fields
        if (!empty($data)) {

            // Get section data
            $section = new SectionModel();
            $section->id = $sectionId;

            // This we append before the slugified path
            $sectionUrl = str_replace('{slug}', '', $section->getUrlFormat());

            // Find matching element by URI (dirty, not all structures have URI's)
            $criteria = craft()->elements->getCriteria(ElementType::Entry);
            $criteria->sectionId = $sectionId;
            $criteria->uri = $sectionUrl.craft()->import->slugify($data);
            $criteria->limit = 1;

            // Return the first found element for connecting
            if ($criteria->total()) {
                $parentId = $criteria->ids()[0];
            }
        }

        return $parentId;
    }

    /**
     * @param string $data
     *
     * @return string
     */
    private function freshenString($data)
    {
        $data = str_replace("\n", '', $data);
        $data = str_replace("\r", '', $data);
        $data = trim($data);

        return $data;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return mixed
     */
    protected function getCraftEdition()
    {
        return craft()->getEdition();
    }
}
