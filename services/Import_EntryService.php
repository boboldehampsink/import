<?php
namespace Craft;

class Import_EntryService extends BaseApplicationComponent 
{

    public function setModel($settings)
    
        // Set up new entry model
        $entry = new EntryModel();
        $entry->sectionId = $settings['section'];
        $entry->typeId = $settings['entrytype'];
        
        return $entry;    
    
    }
    
    public function setCriteria($settings)
    {
    
        // Match with current data
        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        $criteria->limit = null;
        $criteria->status = isset($settings['map']['status']) ? $settings['map']['status'] : null;
    
        // Look in same section when replacing
        $criteria->sectionId = $settings['section'];
    
        return $criteria;
    
    }
    
    public function save($element)
    {
        
        // Save user
        return craft()->entries->saveEntry($element);
    
    }
    
    // Prepare reserved ElementModel values
    public function prepForElementModel(&$fields, EntryModel $entry) 
    {
        
        // Set author
        if(isset($fields[ImportModel::HandleAuthor])) {
            $entry->authorId = intval($fields[ImportModel::HandleAuthor]);
            unset($fields[ImportModel::HandleAuthor]);
        } else {
            $entry->authorId = ($entry->authorId ? $entry->authorId : (craft()->userSession->getUser() ? craft()->userSession->getUser()->id : 1));
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
        if(isset($fields[ImportModel::HandleExpiryDate])) {
            $entry->expiryDate = DateTime::createFromString($fields[ImportModel::HandleExpiryDate], craft()->timezone);
            unset($fields[ImportModel::HandleExpiryDate]);
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
        
        // Set parent id
        if(isset($fields[ImportModel::HandleParent])) {
           
           // Get data
           $data = $fields[ImportModel::HandleParent];
            
            // Fresh up $data
           $data = str_replace("\n", "", $data);
           $data = str_replace("\r", "", $data);
           $data = trim($data);
           
           // Don't connect empty fields
           if(!empty($data)) {
         
               // Find matching element       
               $criteria = craft()->elements->getCriteria(ElementType::Entry);
               $criteria->sectionId = $entry->sectionId;

               // Exact match
               $criteria->search = '"'.$data.'"';
               
               // Return the first found id for connecting
               if($criteria->total()) {
               
                   $entry->parentId = $criteria->first()->id;
                   
               }
           
           }
        
        }
        
        // Return entry
        return $entry;
                    
    }

}