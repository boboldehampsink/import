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
    
    public function save(&$element, $settings)
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
    
        // Set username
        if(isset($fields[ImportModel::HandleUsername])) {
            $entry->username = $fields[ImportModel::HandleUsername];
            unset($fields[ImportModel::HandleUsername]);
        }
        
        // Set firstname
        if(isset($fields[ImportModel::HandleFirstname])) {
            $entry->firstName = $fields[ImportModel::HandleFirstname];
            unset($fields[ImportModel::HandleFirstname]);
        }
        
        // Set lastname
        if(isset($fields[ImportModel::HandleLastname])) {
            $entry->lastName = $fields[ImportModel::HandleLastname];
            unset($fields[ImportModel::HandleLastname]);
        }
        
        // Set email
        if(isset($fields[ImportModel::HandleEmail])) {
            $entry->email = $fields[ImportModel::HandleEmail];
            unset($fields[ImportModel::HandleEmail]);
        }
        
        // Set status
        if(isset($fields[ImportModel::HandleStatus])) {
            $entry->status = $fields[ImportModel::HandleStatus];
            unset($fields[ImportModel::HandleStatus]);
        }
        
        // Return entry
        return $entry;
                    
    }

}