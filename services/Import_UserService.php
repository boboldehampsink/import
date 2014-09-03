<?php
namespace Craft;

class Import_UserService extends BaseApplicationComponent 
{

    public function setModel($settings)
    {
    
        // Set up new user model
        $entry = new UserModel();
        
        return $entry;    
    
    }
    
    public function setCriteria($settings)
    {
    
        // Match with current data
        $criteria = craft()->elements->getCriteria(ElementType::User);
        $criteria->limit = null;
        $criteria->status = isset($settings['map']['status']) ? $settings['map']['status'] : null;
        
        return $criteria;
    
    }
    
    public function save($element, $settings)
    {
        
        // Save user
        if(craft()->users->saveUser($element)) {
        
            // Assign to groups
            craft()->userGroups->assignUserToGroups($element->id, $settings->groups);
        
            return true;
        
        }
        
        return false;
    
    }
    
    // Prepare reserved ElementModel values
    public function prepForElementModel(&$fields, UserModel $entry) 
    {
        
        // Return entry
        return $entry;
                    
    }

}