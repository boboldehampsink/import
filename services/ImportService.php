<?php
namespace Craft;

class ImportService extends BaseApplicationComponent 
{

    public $log = array();

    public function columns($file) 
    {
                
        // Open CSV file       
        $data = $this->_open($file);
        
        // Return only column names
        return array_shift($data);
       
    }
    
    public function data($file) 
    {
        
        // Open CSV file
        $data = $this->_open($file);
        
        // Skip first row
        array_shift($data);

        // Return all data
        return $data;
    
    }
    
    public function row($row, $data, $settings) 
    {
    
        // Get max power
        craft()->config->maxPowerCaptain();
        
        // See if map and data match (could not be due to malformed csv)
        if(count($settings['map']) != count($data)) {
        
            // Log errors when unsuccessful
            $this->log[$row] = craft()->import_history->log($settings['history'], $row, array(array(Craft::t('Columns and data did not match, could be due to malformed CSV row.'))));            
            return;
        
        }
        
        // Check what service we're gonna need
        $service = 'import_' . strtolower($settings['type']);
            
        // Map data to fields
        $fields = array_combine($settings['map'], $data);
        
        // If set, remove fields that will not be imported
        if(isset($fields['dont'])) {
            unset($fields['dont']);
        }
        
        // Set up a model to save according to element type
        $entry = craft()->$service->setModel($settings);
        
        // If unique is non-empty array, we're replacing or deleting
        if(is_array($settings['unique']) && count($settings['unique']) > 1) {
            
            // Set criteria according to elementtype
            $criteria = craft()->$service->setCriteria($settings);
                        
            // Set up criteria model for matching        
            $cmodel = array();    
            foreach($settings['map'] as $key => $value) {
                if(isset($criteria->$settings['map'][$key]) && isset($settings['unique'][$key]) && intval($settings['unique'][$key]) == 1 && !empty($fields[$value])) {
                    $criteria->$settings['map'][$key] = $cmodel[$settings['map'][$key]] = $fields[$value];
                }
            }
                        
            // If there's a match...
            if(count($cmodel) && $criteria->count()) {
                
                // If we're deleting
                if($settings['behavior'] == ImportModel::BehaviorDelete) {
                
                    // Get elements to delete
                    $elements = $criteria->find();
                
                    // Fire an 'onBeforeImportDelete' event
                    Craft::import('plugins.import.events.BeforeImportDeleteEvent');
                    $event = new BeforeImportDeleteEvent($this, array('elements' => $elements));
                    $this->onBeforeImportDelete($event);
                    
                    // Give event the chance to blow off deletion
                    if($event->proceed) {
                    
                        try {
                                
                            // Do it
                            if(!craft()->$service->delete($elements)) {
                            
                                // Log errors when unsuccessful
                                $this->log[$row] = craft()->import_history->log($settings['history'], $row, array(array(Craft::t('Something went wrong while deleting this row.'))));            
                                    
                            }
                            
                        } catch(Exception $e) {
                        
                            // Something went terribly wrong, assume its only this row
                            $this->log[$row] = craft()->import_history->log($settings['history'], $row, array('exception' => array($e->getMessage())));
                        
                        }
                        
                    }
                    
                    // Skip rest and continue
                    return;
                    
                } else {
                
                    // Fill new EntryModel with match
                    $entry = $criteria->first();
                
                } 
                
            } else {
            
                // Else do nothing
                return;
            
            } 
        
        }
        
        // Prepare element model
        $entry = craft()->$service->prepForElementModel($fields, $entry);
        
        try {
        
            // Hook to prepare as appropriate fieldtypes
            array_walk($fields, function(&$data, $handle) {
                return craft()->plugins->call('registerImportOperation', array(&$data, $handle));
            });
        
        } catch(Exception $e) {
        
            // Something went terribly wrong, assume its only this row
            $this->log[$row] = craft()->import_history->log($settings['history'], $row, array('exception' => array($e->getMessage())));
        
        }
        
        // Set fields on entry model
        $entry->setContentFromPost($fields);
        
        try {
        
            // Log
            if(!craft()->$service->save($entry, $settings)) {
            
                // Log errors when unsuccessful
                $this->log[$row] = craft()->import_history->log($settings['history'], $row, $entry->getErrors());
            
            } else {
            
                // Some functions need calling after saving
                craft()->$service->callback($fields, $entry);
                
            }
            
        } catch(Exception $e) {
        
            // Something went terribly wrong, assume its only this row
            $this->log[$row] = craft()->import_history->log($settings['history'], $row, array('exception' => array($e->getMessage())));
        
        }
    
    }
    
    public function finish($settings, $backup) 
    {
    
        craft()->import_history->end($settings['history'], ImportModel::StatusFinished);
        
        if($settings['email']) {
        
            // Gather results
            $results = array(
                'success' => $settings['rows'],
                'errors' => array()
            );
            
            // Gather errors
            foreach($this->log as $line => $result) {
                 $results['errors'][$line] = $result;
            }
            
            // Recalculate successful results
            $results['success'] -= count($results['errors']);
        
            // Prepare the mail
            $email = new EmailModel();
            $emailSettings = craft()->email->getSettings();
            $email->toEmail = $emailSettings['emailAddress'];
            
            // Zip the backup
            if($settings['backup'] && IOHelper::fileExists($backup)) {
                $destZip = craft()->path->getTempPath().IOHelper::getFileName($backup, false).'.zip';
                if(IOHelper::fileExists($destZip)) {
                    IOHelper::deleteFile($destZip, true);
                }
                IOHelper::createFile($destZip);
                if(Zip::add($destZip, $backup, craft()->path->getDbBackupPath())) {
                    $backup = $destZip;
                }
            }
            
            // Set email content
            $email->subject = Craft::t('The import task is finished');
            $email->htmlBody = TemplateHelper::getRaw(craft()->templates->render('import/_email', array(
                'results' => $results,
                'backup' => $backup
            )));
            
            // Send it
            craft()->email->sendEmail($email);
            
        }
    
    }
    
    // Prepare fields for fieldtypes
    public function prepForFieldType(&$data, $handle) 
    {
    
        // Fresh up $data
        $data = StringHelper::convertToUTF8($data);
        $data = trim($data);
                
        // Get field info
        $field = craft()->fields->getFieldByHandle($handle);
        
        // If it's a field ofcourse
        if(!is_null($field)) {
            
            // For some fieldtypes the're special rules
            switch($field->type) {
            
                case ImportModel::FieldTypeEntries:
                
                    // No newlines allowed
                    $data = str_replace("\n", "", $data);
                    $data = str_replace("\r", "", $data);
                    
                    // Don't connect empty fields
                    if(!empty($data)) {
                    
                        // Get field settings
                        $settings = $field->getFieldType()->getSettings();
                
                        // Get source id's for connecting
                        $sectionIds = array();
                        $sources = $settings->sources;
                        if(is_array($sources)) {
                            foreach($sources as $source) {
                                list($type, $id) = explode(':', $source);
                                $sectionIds[] = $id;
                            }
                        }
                                    
                        // Find matching element in sections       
                        $criteria = craft()->elements->getCriteria(ElementType::Entry);
                        $criteria->sectionId = $sectionIds;
                        $criteria->limit = $settings->limit;
 
                        // Get search strings
                        $search = ArrayHelper::stringToArray($data);
                        
                        // Ability to import multiple Assets at once
                        $data = array();
                        
                        // Loop through keywords
                        foreach($search as $query) {
                            
                            // Search
                            $criteria->search = $query;
                            
                            // Add to data
                            $data = array_merge($data, $criteria->ids());
                            
                        }
                    
                    } else {
                    
                        // Return empty array
                        $data = array();
                    
                    }
                                        
                    break;
                
                case ImportModel::FieldTypeCategories:
                    
                    // Don't connect empty fields
                    if(!empty($data)) {
                    
                        // Get field settings
                        $settings = $field->getFieldType()->getSettings();
                                                                                                
                        // Get source id
                        $source = $settings->source;
                        list($type, $id) = explode(':', $source);
                        
                        // Get category data
                        $category = new CategoryModel();
                        $category->groupId = $id;                  
                    
                        // This we append before the slugified path
                        $categoryUrl = str_replace('{slug}', '', $category->getUrlFormat());
                                                            
                        // Find matching element by URI (dirty, not all categories have URI's)        
                        $criteria = craft()->elements->getCriteria(ElementType::Category);
                        $criteria->groupId = $id;
                        $criteria->uri = $categoryUrl . $this->slugify($data);
                        $criteria->limit = $settings->limit;
                        
                        // Return the found id's for connecting
                        $data = $criteria->ids();
                        
                    } else {
                    
                        // Return empty array
                        $data = array();
                    
                    }
                                        
                    break;
                
                case ImportModel::FieldTypeAssets:
                    
                    // Don't connect empty fields
                    if(!empty($data)) {
                    
                        // Get field settings
                        $settings = $field->getFieldType()->getSettings();
                                        
                        // Get source id's for connecting
                        $sourceIds = array();
                        $sources = $settings->sources;
                        if(is_array($sources)) {
                            foreach($sources as $source) {
                                list($type, $id) = explode(':', $source);
                                $sourceIds[] = $id;
                            }
                        }
                                    
                        // Find matching element in sources    
                        $criteria = craft()->elements->getCriteria(ElementType::Asset);
                        $criteria->sourceId = $sourceIds;
                        $criteria->limit = $settings->limit;
                        
                        // Get search strings
                        $search = ArrayHelper::stringToArray($data);
                        
                        // Ability to import multiple Assets at once
                        $data = array();
                        
                        // Loop through keywords
                        foreach($search as $query) {
                            
                            // Search
                            $criteria->search = $query;
                            
                            // Add to data
                            $data = array_merge($data, $criteria->ids());
                            
                        }
                        
                    } else {
                    
                        // Return empty array
                        $data = array();
                    
                    }
                                        
                    break;
                
                case ImportModel::FieldTypeUsers:
                    
                    // Don't connect empty fields
                    if(!empty($data)) {
                    
                        // Get field settings
                        $settings = $field->getFieldType()->getSettings();
                                
                        // Get group id's for connecting
                        $groupIds = array();
                        $sources = $settings->sources;
                        if(is_array($sources)) {
                            foreach($sources as $source) {
                                list($type, $id) = explode(':', $source);
                                $groupIds[] = $id;
                            }
                        }
                                    
                        // Find matching element in sources    
                        $criteria = craft()->elements->getCriteria(ElementType::Asset);
                        $criteria->groupId = $groupIds;
                        $criteria->limit = $settings->limit;
                        
                        // Get search strings
                        $search = ArrayHelper::stringToArray($data);
                        
                        // Ability to import multiple Assets at once
                        $data = array();
                        
                        // Loop through keywords
                        foreach($search as $query) {
                            
                            // Search
                            $criteria->search = $query;
                            
                            // Add to data
                            $data = array_merge($data, $criteria->ids());
                            
                        }
                        
                    } else {
                    
                        // Return empty array
                        $data = array();
                    
                    }
                                        
                    break;
                    
                case ImportModel::FieldTypeNumber:
                    
                    // Parse as number
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
                    $settings = $field->getFieldType()->getSettings();

                    //get field options
                    $options = $settings->getAttribute('options');

                    // find matching option label
                    $labelSelected = false;
                    foreach($options as $option){

                        if($labelSelected){
                            continue;
                        }

                        if($data == $option['label']){
                            $data = $option['value'];
                            //stop looking after first match
                            $labelSelected = true;
                        }

                    }

                    break;

                case ImportModel::FieldTypeCheckboxes:
                case ImportModel::FieldTypeMultiSelect:
                    
                    // Convert to array
                    $data = ArrayHelper::stringToArray($data);
                    
                    break;

                case ImportModel::FieldTypeTags:

                    //get settings
                    $settings = $field->getFieldType()->getSettings();

                    //get tag group id
                    $source = $settings->getAttribute('source');
                    list($type, $groupId) = explode(':', $source);

                    $tags = ArrayHelper::stringToArray($data);
                    $data = array();

                    foreach($tags as $tag){

                        // Find existing tag
                        $criteria = craft()->elements->getCriteria(ElementType::Tag);
                        $criteria->title = $tag;
                        $criteria->groupId = $groupId;

                        if(!$criteria->total()) {

	                        // Create tag if one doesn't already exist
	                        $newtag = new TagModel();
	                        $newtag->getContent()->title = $tag;
	                        $newtag->groupId = $groupId;
	                        
	                        // Save tag
	                        if(craft()->tags->saveTag($newtag)) {
	                            $tagArray = array($newtag->id);
	                        }

                        } else {

                        	$tagArray = $criteria->ids();

                        }

						// Add tags to data array
                        $data = array_merge($data, $tagArray);

                    }

                    break;

            }
        
        }
                                
        return $data;
    
    }
    
    // Function that (almost) mimics Craft's inner slugify process.
    // But... we allow forward slashes to stay, so we can create full uri's.
    public function slugify($slug) 
    {
    
        // Remove HTML tags
        $slug = preg_replace('/<(.*?)>/u', '', $slug);
        
        // Remove inner-word punctuation.
        $slug = preg_replace('/[\'"‘’“”\[\]\(\)\{\}:]/u', '', $slug);

        if (craft()->config->get('allowUppercaseInSlug') === false)
        {
            // Make it lowercase
            $slug = StringHelper::toLowerCase($slug, 'UTF-8');
        }

        // Get the "words".  Split on anything that is not a unicode letter or number. Periods, underscores, hyphens and forward slashes get a pass.
        preg_match_all('/[\p{L}\p{N}\.\/_-]+/u', $slug, $words);
        $words = ArrayHelper::filterEmptyStringsFromArray($words[0]);
        $slug = implode(craft()->config->get('slugWordSeparator'), $words);

        return $slug;
        
    }
    
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
    
    // Special function that handles csv delimiter detection
    protected function _open($file) 
    {
    
        $data = array();
        
        // Automatically detect line endings
        @ini_set('auto_detect_line_endings', true);
        
        // Open file into rows
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        // Detect delimiter from first row
        $delimiters = array();
        $delimiters[ImportModel::DelimiterSemicolon] = substr_count($lines[0], ImportModel::DelimiterSemicolon);
        $delimiters[ImportModel::DelimiterComma]     = substr_count($lines[0], ImportModel::DelimiterComma);
        $delimiters[ImportModel::DelimiterPipe]      = substr_count($lines[0], ImportModel::DelimiterPipe);
        
        // Sort by delimiter with most occurences
        arsort($delimiters, SORT_NUMERIC);
        
        // Give me the keys
        $delimiters = array_keys($delimiters);
        
        // Use first key -> this is the one with most occurences
        $delimiter = array_shift($delimiters);
        
        // Open file and parse csv rows
        $handle = fopen($file, 'r');        
        while(($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                
            // Add row to data array
            $data[] = $row;
        
        }
        fclose($handle);
        
        // Return data array
        return $data;
    
    }
    
    // Fires an "onBeforeImportDelete" event
    public function onBeforeImportDelete(BeforeImportDeleteEvent $event)
    {
        $this->raiseEvent('onBeforeImportDelete', $event);
    }
    
    // Fires an "onImportFinish" event
    public function onImportFinish(ImportFinishEvent $event)
    {
        $this->raiseEvent('onImportFinish', $event);
    }

}
