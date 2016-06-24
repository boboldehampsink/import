<?php

namespace Craft;

/**
 * Import Service.
 *
 * Contains common import logic
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 */
class ImportService extends BaseApplicationComponent
{
    /**
     * Save log.
     *
     * @var array
     */
    public $log = array();

    /**
     * Custom <option> paths.
     *
     * @var array
     */
    public $customOptionPaths = array();

    /**
     * Whether custom option paths have been loaded.
     *
     * @var bool
     */
    private $_loadedOptionPaths = false;

    /**
     * Cache import file data
     *
     * @var array
     */
    private $_data = array();

    /**
     * Read CSV columns.
     *
     * @param string $file
     *
     * @return array
     */
    public function columns($file)
    {
        // Open CSV file
        $data = $this->_open($file);

        // Return only column names
        return array_shift($data);
    }

    /**
     * Get CSV data.
     *
     * @param string $file
     *
     * @return array
     */
    public function data($file)
    {
        // Open CSV file
        $data = $this->_open($file);

        // Skip first row
        array_shift($data);

        // Return all data
        return $data;
    }

    /**
     * Import row.
     *
     * @param @param int   $row
     * @param array        $data
     * @param array|object $settings
     *
     * @throws Exception
     */
    public function row($row, array $data, $settings)
    {
        // See if map and data match (could not be due to malformed csv)
        if (count($settings['map']) != count($data)) {

            // Log errors when unsuccessful
            $this->log[$row] = craft()->import_history->log($settings['history'], $row, array(array(Craft::t('Columns and data did not match, could be due to malformed CSV row.'))));

            return;
        }

        // Check what service we're gonna need
        if (!($service = $this->getService($settings['type']))) {
            throw new Exception(Craft::t('Unknown Element Type Service called.'));
        }

        // Map data to fields
        $fields = array_combine($settings['map'], $data);

        // If set, remove fields that will not be imported
        if (isset($fields['dont'])) {
            unset($fields['dont']);
        }

        // Set up a model to save according to element type
        $entry = $service->setModel($settings);

        // If unique is non-empty array, we're replacing or deleting
        if (is_array($settings['unique']) && count($settings['unique']) > 1) {
            $entry = $this->replaceOrDelete($row, $settings, $service, $fields, $entry);
            if ($entry === null) {
                return;
            }
        }

        // Prepare element model
        $entry = $service->prepForElementModel($fields, $entry);

        try {

            // Hook to prepare as appropriate fieldtypes
            array_walk($fields, function (&$data, $handle) {
                return craft()->plugins->call('registerImportOperation', array(&$data, $handle));
            });
        } catch (Exception $e) {

            // Something went terribly wrong, assume its only this row
            $this->log[$row] = craft()->import_history->log($settings['history'], $row, array('exception' => array($e->getMessage())));
        }

        // Set fields on entry model
        $entry->setContentFromPost($fields);

        try {

            // Hook called after all the field values are set, allowing for modification
            // of the entire entry before it's saved. Include the mapping table and row data.
            craft()->plugins->call('modifyImportRow', array($entry, $settings['map'], $data));
        } catch (Exception $e) {

            // Something went terribly wrong, assume its only this row
            $this->log[$row] = craft()->import_history->log($settings['history'], $row, array('exception' => array($e->getMessage())));
        }

        try {

            // Log
            if (!$service->save($entry, $settings)) {

                // Log errors when unsuccessful
                $this->log[$row] = craft()->import_history->log($settings['history'], $row, $entry->getErrors());
            } else {

                // Some functions need calling after saving
                $service->callback($fields, $entry);
            }
        } catch (Exception $e) {

            // Something went terribly wrong, assume its only this row
            $this->log[$row] = craft()->import_history->log($settings['history'], $row, array('exception' => array($e->getMessage())));
        }
    }

    /**
     * @param int                $row
     * @param array              $settings
     * @param IImportElementType $service
     * @param array              $fields
     *
     * @return null|BaseElementModel
     *
     * @throws Exception
     */
    private function replaceOrDelete($row, &$settings, IImportElementType $service, array $fields)
    {
        // Set criteria according to elementtype
        $criteria = $service->setCriteria($settings);

        // Set up criteria model for matching
        $cmodel = array();
        foreach ($settings['map'] as $key => $value) {
            if (isset($settings['unique'][$key]) && intval($settings['unique'][$key]) == 1 && $value != 'dont') {
                // Unique value should have a value
                if (trim($fields[$value]) != '') {
                    $criteria->$value = $cmodel[$value] = $fields[$value];
                } else {
                    // Else stop the operation - chance of success is only small
                    $this->log[$row] = craft()->import_history->log($settings['history'], $row, array(array(Craft::t('Tried to match criteria but its value was not set.'))));

                    return;
                }
            }
        }

        // If there's a match...
        if (count($cmodel) && $criteria->count()) {

            // Get current user
            $currentUser = craft()->users->getUserById($settings['user']);

            // If we're deleting
            if ($currentUser->can('delete') && $settings['behavior'] == ImportModel::BehaviorDelete) {

                // Get elements to delete
                $elements = $criteria->find();

                // Fire an 'onBeforeImportDelete' event
                $event = new Event($this, array('elements' => $elements));
                $this->onBeforeImportDelete($event);

                // Give event the chance to blow off deletion
                if ($event->performAction) {
                    try {

                        // Do it
                        if (!$service->delete($elements)) {

                            // Log errors when unsuccessful
                            $this->log[$row] = craft()->import_history->log($settings['history'], $row, array(array(Craft::t('Something went wrong while deleting this row.'))));
                        }
                    } catch (Exception $e) {

                        // Something went terribly wrong, assume its only this row
                        $this->log[$row] = craft()->import_history->log($settings['history'], $row, array('exception' => array($e->getMessage())));
                    }
                }

                // Skip rest and continue
                return;
            } elseif ($currentUser->can('append') || $currentUser->can('replace')) {

                // Fill new EntryModel with match
                return $criteria->first();
            } else {

                // No permissions!
                throw new Exception(Craft::t('Tried to import without permission.'));
            }
        }
        // Else do nothing
        return;
    }

    /**
     * Finish importing.
     *
     * @param array  $settings
     * @param string $backup
     */
    public function finish($settings, $backup)
    {
        craft()->import_history->end($settings['history'], ImportModel::StatusFinished);

        if ($settings['email']) {

            // Gather results
            $results = array(
                'success' => $settings['rows'],
                'errors' => array(),
            );

            // Gather errors
            foreach ($this->log as $line => $result) {
                $results['errors'][$line] = $result;
            }

            // Recalculate successful results
            $results['success'] -= count($results['errors']);

            // Prepare the mail
            $email = new EmailModel();
            $emailSettings = craft()->email->getSettings();
            $email->toEmail = $emailSettings['emailAddress'];

            // Get current user
            $currentUser = craft()->users->getUserById($settings['user']);

            // Zip the backup
            $backup = $this->saveBackup($settings, $backup, $currentUser);

            // Set email content
            $email->subject = Craft::t('The import task is finished');
            $email->htmlBody = TemplateHelper::getRaw(craft()->templates->render('import/_email', array(
                'results' => $results,
                'backup' => $backup,
            )));

            // Send it
            craft()->email->sendEmail($email);
        }
    }

    /**
     * Prepare fields for fieldtypes.
     *
     * @param string &$data
     * @param string $handle
     *
     * @return mixed
     */
    public function prepForFieldType(&$data, $handle)
    {
        // Fresh up $data
        $data = StringHelper::convertToUTF8($data);
        $data = trim($data);

        // Get field info
        $field = craft()->fields->getFieldByHandle($handle);

        // If it's a field ofcourse
        if (!is_null($field)) {

            // For some fieldtypes the're special rules
            switch ($field->type) {

                case ImportModel::FieldTypeEntries:

                    // No newlines allowed
                    $data = str_replace("\n", '', $data);
                    $data = str_replace("\r", '', $data);

                    // Don't connect empty fields
                    if (!empty($data)) {
                        $data = $this->prepEntriesFieldType($data, $field);
                    } else {
                        $data = array();
                    }

                    break;

                case ImportModel::FieldTypeCategories:

                    if (!empty($data)) {
                        $data = $this->prepCategoriesFieldType($data, $field);
                    } else {
                        $data = array();
                    }

                    break;

                case ImportModel::FieldTypeAssets:

                    if (!empty($data)) {
                        $data = $this->prepAssetsFieldType($data, $field);
                    } else {
                        $data = array();
                    }

                    break;

                case ImportModel::FieldTypeUsers:

                    if (!empty($data)) {
                        $data = $this->prepUsersFieldType($data, $field);
                    } else {
                        $data = array();
                    }

                    break;

                case ImportModel::FieldTypeTags:

                    $data = $this->prepTagsFieldType($data, $field);

                    break;

                case ImportModel::FieldTypeNumber:

                    // Parse as numberx
                    $data = LocalizationHelper::normalizeNumber($data);

                    // Parse as float
                    $data = floatval($data);

                    break;

                case ImportModel::FieldTypeDate:

                    // Parse date from string
                    $data = DateTimeHelper::formatTimeForDb(DateTimeHelper::fromString($data, craft()->timezone));

                    break;

                case ImportModel::FieldTypeRadioButtons:
                case ImportModel::FieldTypeDropdown:

                    //get field settings
                    $data = $this->prepDropDownFieldType($data, $field);

                    break;

                case ImportModel::FieldTypeCheckboxes:
                case ImportModel::FieldTypeMultiSelect:

                    // Convert to array
                    $data = ArrayHelper::stringToArray($data);

                    break;

                case ImportModel::FieldTypeLightSwitch:

                    // Convert yes/no values to boolean
                    switch ($data) {

                        case Craft::t('Yes');
                            $data = true;
                            break;

                        case Craft::t('No');
                            $data = false;
                            break;

                    }

                    break;

            }
        }

        return $data;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return TagModel
     */
    protected function getNewTagModel()
    {
        $newtag = new TagModel();

        return $newtag;
    }

    /**
     * Get service to use for importing.
     *
     * @param stirng $elementType
     *
     * @return IImportElementType|bool
     */
    public function getService($elementType)
    {
        // Check if there's a service for this element type elsewhere
        $service = craft()->plugins->callFirst('registerImportService', array(
            'elementType' => $elementType,
        ));

        // If not, do internal check
        if ($service == null) {

            // Get from right elementType
            $service = 'import_'.strtolower($elementType);
        }

        // Check if elementtype can be imported
        if (isset(craft()->$service) && craft()->$service instanceof IImportElementType) {

            // Return this service
            return craft()->$service;
        }

        return false;
    }

    /**
     * Get path to fieldtype's custom <option> template.
     *
     * @param string $fieldHandle
     *
     * @return string
     */
    public function getCustomOption($fieldHandle)
    {
        // If option paths haven't been loaded
        if (!$this->_loadedOptionPaths) {

            // Call hook for all plugins
            $responses = craft()->plugins->call('registerImportOptionPaths');

            // Loop through responses from each plugin
            foreach ($responses as $customPaths) {

                // Append custom paths to master list
                $this->customOptionPaths = array_merge($this->customOptionPaths, $customPaths);
            }

            // Option paths have been loaded
            $this->_loadedOptionPaths = true;
        }

        // If fieldtype has been registered and is not falsey
        if (array_key_exists($fieldHandle, $this->customOptionPaths) && $this->customOptionPaths[$fieldHandle]) {

            // Return specified custom path
            return $this->customOptionPaths[$fieldHandle];
        }

        return false;
    }

    /**
     * Function that (almost) mimics Craft's inner slugify process.
     * But... we allow forward slashes to stay, so we can create full uri's.
     *
     * @param string $slug
     *
     * @return string
     */
    public function slugify($slug)
    {
        // Remove HTML tags
        $slug = preg_replace('/<(.*?)>/u', '', $slug);

        // Remove inner-word punctuation.
        $slug = preg_replace('/[\'"‘’“”\[\]\(\)\{\}:]/u', '', $slug);

        if (craft()->config->get('allowUppercaseInSlug') === false) {
            // Make it lowercase
            $slug = StringHelper::toLowerCase($slug);
        }

        // Get the "words".  Split on anything that is not a unicode letter or number. Periods, underscores, hyphens and forward slashes get a pass.
        preg_match_all('/[\p{L}\p{N}\.\/_-]+/u', $slug, $words);
        $words = ArrayHelper::filterEmptyStringsFromArray($words[0]);
        $slug = implode(craft()->config->get('slugWordSeparator'), $words);

        return $slug;
    }

    /**
     * Special function that handles csv delimiter detection.
     *
     * @param string $file
     *
     * @return array
     */
    protected function _open($file)
    {
        if(!count($this->_data)) {

            // Turn asset into a file
            $asset = craft()->assets->getFileById($file);
            $source = $asset->getSource();
            $sourceType = $source->getSourceType();
            $file = $sourceType->getLocalCopy($asset);

            // Check if file exists in the first place
            if (file_exists($file)) {

                // Automatically detect line endings
                @ini_set('auto_detect_line_endings', true);

                // Open file into rows
                $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                // Detect delimiter from first row
                $delimiters = array();
                $delimiters[ImportModel::DelimiterSemicolon] = substr_count($lines[0], ImportModel::DelimiterSemicolon);
                $delimiters[ImportModel::DelimiterComma] = substr_count($lines[0], ImportModel::DelimiterComma);
                $delimiters[ImportModel::DelimiterPipe] = substr_count($lines[0], ImportModel::DelimiterPipe);

                // Sort by delimiter with most occurences
                arsort($delimiters, SORT_NUMERIC);

                // Give me the keys
                $delimiters = array_keys($delimiters);

                // Use first key -> this is the one with most occurences
                $delimiter = array_shift($delimiters);

                // Open file and parse csv rows
                $handle = fopen($file, 'r');
                while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {

                    // Add row to data array
                    $this->_data[] = $row;
                }
                fclose($handle);
            }
        }

        // Return data array
        return $this->_data;
    }

    /**
     * @codeCoverageIgnore
     *
     * Function to use when debugging.
     *
     * @param array|object $settings
     * @param int          $history
     * @param int          $step
     */
    public function debug($settings, $history, $step)
    {
        // Open file
        $data = $this->data($settings['file']);

        // Adjust settings for one row
        $model = Import_HistoryRecord::model()->findById($history);
        $model->rows = 1;
        $model->save();

        // Import row
        $this->row($step, $data[$step], $settings);

        // Finish
        $this->finish($settings, false);

        // Redirect to history
        craft()->request->redirect('import/history');
    }

    /**
     * @codeCoverageIgnore
     *
     * Fires an "onBeforeImportDelete" event.
     *
     * @param Event $event
     */
    public function onBeforeImportDelete(Event $event)
    {
        $this->raiseEvent('onBeforeImportDelete', $event);
    }

    /**
     * @codeCoverageIgnore
     *
     * Fires an "onImportStart" event.
     *
     * @param Event $event
     */
    public function onImportStart(Event $event)
    {
        $this->raiseEvent('onImportStart', $event);
    }

    /**
     * @codeCoverageIgnore
     *
     * Fires an "onImportFinish" event.
     *
     * @param Event $event
     */
    public function onImportFinish(Event $event)
    {
        $this->raiseEvent('onImportFinish', $event);
    }

    /**
     * @param string     $data
     * @param FieldModel $field
     *
     * @return array
     */
    private function prepTagsFieldType($data, FieldModel $field)
    {
        // Get field settings
        $settings = $field->getFieldType()->getSettings();

        // Get tag group id
        $source = $settings->getAttribute('source');
        list($type, $groupId) = explode(':', $source);

        $tags = ArrayHelper::stringToArray($data);
        $data = array();

        foreach ($tags as $tag) {

            // Find existing tag
            $criteria = craft()->elements->getCriteria(ElementType::Tag);
            $criteria->title = $tag;
            $criteria->groupId = $groupId;
            $criteria->status = null;
            $criteria->localeEnabled = null;

            $tagArray = array();

            if (!$criteria->total()) {

                // Create tag if one doesn't already exist
                $newtag = $this->getNewTagModel();
                $newtag->getContent()->title = $tag;
                $newtag->groupId = $groupId;

                // Save tag
                if (craft()->tags->saveTag($newtag)) {
                    $tagArray = array($newtag->id);
                }
            } else {
                $tagArray = $criteria->ids();
            }

            // Add tags to data array
            $data = array_merge($data, $tagArray);
        }

        return $data;
    }

    /**
     * @param mixed      $data
     * @param FieldModel $field
     *
     * @return mixed
     */
    private function prepDropDownFieldType($data, FieldModel $field)
    {
        $settings = $field->getFieldType()->getSettings();

        //get field options
        $options = $settings->getAttribute('options');

        // find matching option label
        $labelSelected = false;
        foreach ($options as $option) {
            if ($labelSelected) {
                continue;
            }

            if ($data == $option['label']) {
                $data = $option['value'];
                //stop looking after first match
                $labelSelected = true;
            }
        }

        return $data;
    }

    /**
     * @param string     $data
     * @param FieldModel $field
     *
     * @return array
     */
    private function prepUsersFieldType($data, FieldModel $field)
    {
        // Get field settings
        $settings = $field->getFieldType()->getSettings();

        // Get group id's for connecting
        $groupIds = array();
        $sources = $settings->getAttribute('sources');
        if (is_array($sources)) {
            foreach ($sources as $source) {
                list(, $id) = explode(':', $source);
                $groupIds[] = $id;
            }
        }

        // Find matching element in sources
        $criteria = craft()->elements->getCriteria(ElementType::User);
        $criteria->groupId = $groupIds;
        $criteria->limit = $settings->limit;
        $criteria->status = null;
        $criteria->localeEnabled = null;

        // Get search strings
        $search = ArrayHelper::stringToArray($data);

        // Ability to import multiple Users at once
        $data = array();

        // Loop through keywords
        foreach ($search as $query) {

            // Search
            $criteria->search = $query;

            // Add to data
            $data = array_merge($data, $criteria->ids());
        }

        return $data;
    }

    /**
     * @param string     $data
     * @param FieldModel $field
     *
     * @return array
     */
    private function prepAssetsFieldType($data, FieldModel $field)
    {
        // Get field settings
        $settings = $field->getFieldType()->getSettings();

        // Get folder id's for connecting
        $folderIds = array();
        $folders = $settings->getAttribute('sources');
        if (is_array($folders)) {
            foreach ($folders as $folder) {
                list(, $id) = explode(':', $folder);
                $folderIds[] = $id;
            }
        }

        // Find matching element in folders
        $criteria = craft()->elements->getCriteria(ElementType::Asset);
        $criteria->folderId = $folderIds;
        $criteria->limit = $settings->limit;
        $criteria->status = null;
        $criteria->localeEnabled = null;

        // Get search strings
        $search = ArrayHelper::stringToArray($data);

        // Ability to import multiple Assets at once
        $data = array();

        // Loop through keywords
        foreach ($search as $query) {

            // Search
            $criteria->search = $query;

            // Add to data
            $data = array_merge($data, $criteria->ids());
        }

        return $data;
    }

    /**
     * @param string     $data
     * @param FieldModel $field
     *
     * @return array
     */
    private function prepCategoriesFieldType($data, FieldModel $field)
    {
        // Get field settings
        $settings = $field->getFieldType()->getSettings();

        // Get source id
        $source = $settings->getAttribute('source');
        list(, $id) = explode(':', $source);

        // Get category data
        $category = new CategoryModel();
        $category->groupId = $id;

        // This we append before the slugified path
        $categoryUrl = str_replace('{slug}', '', $category->getUrlFormat());

        // Find matching elements in categories
        $criteria = craft()->elements->getCriteria(ElementType::Category);
        $criteria->groupId = $id;
        $criteria->limit = $settings->limit;
        $criteria->status = null;
        $criteria->localeEnabled = null;

        // Get search strings
        $search = ArrayHelper::stringToArray($data);

        // Ability to import multiple Categories at once
        $data = array();

        // Loop through keywords
        foreach ($search as $query) {

            // Find matching element by URI (dirty, not all categories have URI's)
            $criteria->uri = $categoryUrl.$this->slugify($query);

            // Add to data
            $data = array_merge($data, $criteria->ids());
        }

        return $data;
    }

    /**
     * @param string     $data
     * @param FieldModel $field
     *
     * @return array
     */
    private function prepEntriesFieldType($data, FieldModel $field)
    {
        // Get field settings
        $settings = $field->getFieldType()->getSettings();

        // Get source id's for connecting
        $sectionIds = array();
        $sources = $settings->getAttribute('sources');
        if (is_array($sources)) {
            foreach ($sources as $source) {
                list($type, $id) = explode(':', $source);
                $sectionIds[] = $id;
            }
        }

        // Find matching element in sections
        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        $criteria->sectionId = $sectionIds;
        $criteria->limit = $settings->limit;
        $criteria->status = null;
        $criteria->localeEnabled = null;

        // Get search strings
        $search = ArrayHelper::stringToArray($data);

        // Ability to import multiple Assets at once
        $data = array();

        // Loop through keywords
        foreach ($search as $query) {

            // Search
            $criteria->search = $query;

            // Add to data
            $data = array_merge($data, $criteria->ids());
        }

        return $data;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param array     $settings
     * @param string    $backup
     * @param UserModel $currentUser
     *
     * @return string Backup filename
     */
    protected function saveBackup($settings, $backup, $currentUser)
    {
        if ($currentUser->can('backup') && $settings['backup'] && IOHelper::fileExists($backup)) {
            $destZip = craft()->path->getTempPath().IOHelper::getFileName($backup, false).'.zip';
            if (IOHelper::fileExists($destZip)) {
                IOHelper::deleteFile($destZip, true);
            }
            IOHelper::createFile($destZip);
            if (Zip::add($destZip, $backup, craft()->path->getDbBackupPath())) {
                $backup = $destZip;
            }
        }

        return $backup;
    }
}
