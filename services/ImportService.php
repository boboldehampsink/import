<?php
namespace Craft;

class ImportService extends BaseApplicationComponent {

    public $log = array();

    public function columns($file) {
                
        // Open CSV file       
        $data = $this->_open($file);
        
        // Return only column names
        return array_shift($data);
       
    }
    
    public function data($file) {
        
        // Open CSV file
        $data = $this->_open($file);
        
        // Skip first row
        array_shift($data);

        // Return all data
        return $data;
    
    }
    
    public function row($row, $data, $settings) {
    
        // Get max power
        craft()->config->maxPowerCaptain();
        
        // See if map and data match (could not be due to malformed csv)
        if(count($settings['map']) != count($data)) {
        
            // Log errors when unsuccessful
            $this->log[($row+1)] = array(array(Craft::t('Columns and data did not match, could be due to malformed CSV row.')));
            return;
        
        }
            
        // Map data to fields
        $fields = array_combine($settings['map'], $data);
        
        // If set, remove fields that will not be imported
        if(isset($fields['dont'])) {
            unset($fields['dont']);
        }
        
        // Set up new entry model
        $entry = new EntryModel();
        $entry->sectionId = $settings['section'];
        $entry->typeId = $settings['entrytype'];
        
        // If unique is non-empty array, we're replacing or deleting
        if(is_array($settings['unique']) && count($settings['unique']) > 1) {
        
            // Match with current data
            $criteria = craft()->elements->getCriteria(ElementType::Entry);
            $criteria->sectionId = $settings['section'];
            foreach($settings['map'] as $key => $value) {
                if(isset($settings['unique'][$key]) && $settings['unique'][$key] == 1) {
                    $criteria->$settings['map'][$key] = $fields[$value];
                }
            } 
            
            // If there's a match...
            if($criteria->total()) {
                
                // If we're deleting
                if($settings['behavior'] == ImportModel::BehaviorDelete) {
                                
                    // Do it
                    craft()->elements->deleteElementById($criteria->ids());
                    
                    // Skip rest and continue
                    return;
                    
                }  else {
                
                    // Fill new EntryModel with match
                    $entry = $criteria->first();
                
                } 
                
            } else {
            
                // Else do nothing
                return;
            
            } 
        
        }
        
        // Prepare entry model
        $entry = $this->prepForEntryModel($fields, $entry);
        
        // Hook to prepare as appropriate fieldtypes
        array_walk($fields, function(&$data, $handle) {
            return craft()->plugins->call('registerImportOperation', array(&$data, $handle));
        });
        
        // Set fields on entry model
        $entry->setContentFromPost($fields);
        
        // Save entry
        if(!craft()->entries->saveEntry($entry)) {
        
            // Log errors when unsuccessful
            $this->log[($row+1)] = $entry->getErrors();
        
        }
    
    }
    
    public function finish($rows) {
    
        // Gather results
        $results = array(
        	'success' => $rows,
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
        
        // Set email content
        $email->subject = Craft::t('The import task is finished');
        $email->htmlBody = TemplateHelper::getRaw(craft()->templates->render('import/_email', array(
    	    'results' => $results
    	)));
    	
    	// Send it
    	craft()->email->sendEmail($email);
    
    }

    // Special function that handles csv delimiter detection
    protected function _open($file) {
    
        $data = array();
        
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
    
    // Prepare reserved EntryModel values
    public function prepForEntryModel(&$fields, EntryModel $entry) {
        
        // Set Author
        if(isset($fields[ImportModel::HandleAuthor])) {
            $entry->authorId = intval($fields[ImportModel::HandleAuthor]);
            unset($fields[ImportModel::HandleAuthor]);
        } else {
            $entry->authorId = ($entry->authorId ? $entry->authorId : craft()->userSession->getUser()->id);
        }
        
        // Set slug
        if(isset($fields[ImportModel::HandleSlug])) {
            $entry->slug = ElementHelper::createSlug($fields[ImportModel::HandleSlug]);
            unset($fields[ImportModel::HandleSlug]);
        }
        
        // Set postdate
        if(isset($fields[ImportModel::HandlePostDate])) {
            $entry->postDate = DateTime::createFromString($fields[ImportModel::HandlePostDate], craft()->timezone);
            unset($fields[ImportModel::HandlePostDate]);
        }
        
        // Set expiry date
        if(isset($fields[ImportModel::HandlePostDate])) {
            $entry->expiryDate = DateTime::createFromString($fields[ImportModel::ExpiryDate], craft()->timezone);
            unset($fields[ImportModel::HandlePostDate]);
        }
        
        // Set enabled
        if(isset($fields[ImportModel::HandleEnabled])) {
            $entry->enabled = (bool)$fields[ImportModel::HandleEnabled];
            unset($fields[ImportModel::HandleEnabled]);
        }
        
        // Set title
        if(isset($fields[ImportModel::HandleTitle])) {
            $entry->getContent()->title = $fields[ImportModel::HandleTitle];
            unset($fields[ImportModel::HandleTitle]);
        }

        // Return entry
        return $entry;
                    
    }
    
    // Prepare fields for fieldtypes
    public function prepForFieldType(&$data, $handle) {
                
        // Get field info
        $field = craft()->fields->getFieldByHandle($handle);
        
        // If it's a field ofcourse
        if(!is_null($field)) {
            
            // For some fieldtypes the're special rules
            switch($field->type) {
            
                case ImportModel::FieldTypeEntries:
                
                    // Fresh up $data
                    $data = str_replace("\n", "", $data);
                    $data = str_replace("\r", "", $data);
                    $data = trim($data);
                    
                    // Don't connect empty fields
                    if(!empty($data)) {
                
                        // Get source id's for connecting
                        $sectionIds = array();
                        $sources = $field->getFieldType()->getSettings()->sources;
                        if(is_array($sources)) {
                            foreach($sources as $source) {
                                list($type, $id) = explode(':', $source);
                                $sectionIds[] = $id;
                            }
                        }
                                    
                        // Find matching element in sections       
                        $criteria = craft()->elements->getCriteria(ElementType::Entry);
                        $criteria->sectionId = $sectionIds;
 
                        // "Loose" matching for easier connecting
                        $data = implode(' OR ', ArrayHelper::stringToArray($data));
                        $criteria->search = $data;
                        
                        // Return the found id's for connecting
                        $data = $criteria->ids();
                    
                    } else {
                    
                        // Return empty array
                        $data = array();
                    
                    }
                                        
                break;
                
                case ImportModel::FieldTypeCategories:
                
                    // Fresh up $data
                    $data = trim($data);
                    
                    // Don't connect empty fields
                    if(!empty($data)) {
                                                                        
                        // Get source id
                        $source = $field->getFieldType()->getSettings()->source;
                        list($type, $id) = explode(':', $source);
                        
                        // Get category data
                        $category = new CategoryModel();
                        $category->groupId = $id;                    
                    
                        // This we append before the slugified path
                        $categoryUrl = str_replace('/{slug}', '/', $category->getUrlFormat());
                                                            
                        // Find matching element by URI (dirty, not all categories have URI's)        
                        $criteria = craft()->elements->getCriteria(ElementType::Category);
                        $criteria->groupId = $id;
                        $criteria->uri = $categoryUrl . $this->_slugify($data);
                        
                        // Return the found id's for connecting
                        $data = $criteria->ids();
                        
                    } else {
                    
                        // Return empty array
                        $data = array();
                    
                    }
                                        
                break;
                
                case ImportModel::FieldTypeAssets:
                
                    // Fresh up $data
                    $data = trim($data);
                    
                    // Don't connect empty fields
                    if(!empty($data)) {
                
                        // Get source id's for connecting
                        $sourceIds = array();
                        $sources = $field->getFieldType()->getSettings()->sources;
                        if(is_array($sources)) {
                            foreach($sources as $source) {
                                list($type, $id) = explode(':', $source);
                                $sourceIds[] = $id;
                            }
                        }
                                    
                        // Find matching element in sources    
                        $criteria = craft()->elements->getCriteria(ElementType::Asset);
                        $criteria->sourceId = $sourceIds;
                        
                        // Ability to import multiple Assets at once
                        $data = implode(' OR ', ArrayHelper::stringToArray($data));
                        $criteria->search = $data;
                                                
                        // Return the found id's for connecting
                        $data = $criteria->ids();
                        
                    } else {
                    
                        // Return empty array
                        $data = array();
                    
                    }
                                        
                break;
                
                case ImportModel::FieldTypeUsers:
                
                    // Fresh up $data
                    $data = trim($data);
                    
                    // Don't connect empty fields
                    if(!empty($data)) {
                                
                        // Find matching element        
                        $criteria = craft()->elements->getCriteria(ElementType::User);
                        
                        // Ability to import multiple Users at once
                        $data = implode(' OR ', ArrayHelper::stringToArray($data));
                        $criteria->search = $data;
                                                
                        // Return the found id's for connecting
                        $data = $criteria->ids();
                        
                    } else {
                    
                        // Return empty array
                        $data = array();
                    
                    }
                                        
                break;
            
            }
        
        }
                                
        return $data;
    
    }
    
    // Function that (almost) mimics Craft's inner slugify process.
    // But... we allow forward slashes to stay, so we can create full uri's.
    protected function _slugify($slug) {
    
        // Remove HTML tags
        $slug = preg_replace('/<(.*?)>/u', '', $slug);
        
        // Remove inner-word punctuation.
        $slug = preg_replace('/[\'"‘’“”]/u', '', $slug);
        
        // Make it lowercase
        $slug = mb_strtolower($slug, 'UTF-8');
        
        // Get the "words".  Split on anything that is not a unicode letter or number.
        // Periods are OK too.
        // Forward slashes are OK too.
        preg_match_all('/[\p{L}\p{N}\.\/]+/u', $slug, $words);
        $words = ArrayHelper::filterEmptyStringsFromArray($words[0]);
        $slug = implode('-', $words);
        
        return $slug;
        
    }

}