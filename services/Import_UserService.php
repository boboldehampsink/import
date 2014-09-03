<?php
namespace Craft;

class Import_UserService extends BaseApplicationComponent 
{

    public function setModel($settings)
    
        // Set up new user model
        $entry = new UserModel();
        $entry->groups = $settings['groups'];
        
        return $entry;    
    
    }
    
    public function setCriteria($criteria, $settings)
    {
    
        // Match with current data
        $criteria = craft()->elements->getCriteria(ElementType::User);
        $criteria->limit = null;
        $criteria->status = isset($settings['map']['status']) ? $settings['map']['status'] : null;
        
        return $criteria;
    
    }
    
    public function save($element)
    {
        
        // Save user
        return craft()->users->saveUser($element);
    
    }
    
    // Prepare reserved ElementModel values
    public function prepForElementModel(&$fields, EntryModel $entry) 
    {
        
        // Return entry
        return $entry;
                    
    }

}