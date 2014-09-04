<?php
namespace Craft;

class Import_UserService extends BaseApplicationComponent 
{


    public function getGroups()
    {
    
        // Get usergroups
        $groups = craft()->userGroups->getAllGroups();
        
        // Return when groups found
        if(count($groups)) {
        
            return $groups;
            
        }
        
        // Still return true when no groups found
        return true;
    
    }

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
            craft()->userGroups->assignUserToGroups($element->id, $settings['elementvars']['groups']);
        
            return true;
        
        }
        
        return false;
    
    }
    
    // Prepare reserved ElementModel values
    public function prepForElementModel(&$fields, UserModel $entry) 
    {
    
        // Set username
        $username = Import_UserModel::HandleUsername;
        if(isset($fields[$username])) {
            $entry->$username = $fields[$username];
            unset($fields[$username]);
        } elseif(isset($fields[$username])) {
            $entry->$username = $fields[$username];
        }
        
        // Set photo
        $photo = Import_UserModel::HandlePhoto;
        if(isset($fields[$photo])) {
            $entry->$photo = $fields[$photo];
        }
        
        // Set firstname
        $firstName = Import_UserModel::HandleFirstname;
        if(isset($fields[$firstName])) {
            $entry->$firstName = $fields[$firstName];
            unset($fields[$firstName]);
        }
        
        // Set lastname
        $lastName = Import_UserModel::HandleLastname;
        if(isset($fields[$lastName])) {
            $entry->$lastName = $fields[$lastName];
            unset($fields[$lastName]);
        }
        
        // Set email
        $email = Import_UserModel::HandleEmail;
        if(isset($fields[$email])) {
            $entry->$email = $fields[$email];
            unset($fields[$email]);
        }
        
        // Set status
        $status = Import_UserModel::HandleStatus;
        if(isset($fields[$status])) {
            $entry->$status = $fields[$status];
            unset($fields[$status]);
        }
        
        // Return entry
        return $entry;
                    
    }

}