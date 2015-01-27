<?php
namespace Craft;

class Import_UserService extends BaseApplicationComponent 
{


    public function getGroups()
    {
    
        // Check if usergroups are allowed in this installation
        if(isset(craft()->userGroups)) {
    
            // Get usergroups
            $groups = craft()->userGroups->getAllGroups();
            
            // Return when groups found
            if(count($groups)) {
            
                return $groups;
                
            }
        
            // Still return true when no groups found
            return true;
        
        }
        
        // Else, dont proceed with the user element
        return false;
    
    }

    public function setModel($settings)
    {
    
        // Set up new user model
        $element = new UserModel();
        
        return $element;    
    
    }
    
    public function setCriteria($settings)
    {
    
        // Match with current data
        $criteria = craft()->elements->getCriteria(ElementType::User);
        $criteria->limit = null;
        $criteria->status = isset($settings['map']['status']) ? $settings['map']['status'] : null;
        
        return $criteria;
    
    }
    
    public function delete($elements)
    {
    
        $return = true;
    
        // Delete users
        foreach($elements as $element) {
        
            if(!craft()->users->deleteUser($element)) {
            
                $return = false;
            
            }
            
        }
        
        return $return;
    
    }
    
    // Prepare reserved ElementModel values
    public function prepForElementModel(&$fields, UserModel $element) 
    {
    
        // Set username
        $username = Import_ElementModel::HandleUsername;
        if(isset($fields[$username])) {
            $element->$username = $fields[$username];
            unset($fields[$username]);
        }
        
        // Set photo
        $photo = Import_ElementModel::HandlePhoto;
        if(isset($fields[$photo])) {
            $element->$photo = $fields[$photo];
        }
        
        // Set firstname
        $firstName = Import_ElementModel::HandleFirstname;
        if(isset($fields[$firstName])) {
            $element->$firstName = $fields[$firstName];
            unset($fields[$firstName]);
        }
        
        // Set lastname
        $lastName = Import_ElementModel::HandleLastname;
        if(isset($fields[$lastName])) {
            $element->$lastName = $fields[$lastName];
            unset($fields[$lastName]);
        }
        
        // Set email
        $email = Import_ElementModel::HandleEmail;
        if(isset($fields[$email])) {
            $element->$email = $fields[$email];
            unset($fields[$email]);

            // Set email as username
            if(craft()->config->get('useEmailAsUsername')) {
                $element->$username = $element->$email;
            }
        }
        
        // Set status
        $status = Import_ElementModel::HandleStatus;
        if(isset($fields[$status])) {
            $element->$status = $fields[$status];
            unset($fields[$status]);
        }
        
        // Set locale
        $locale = Import_ElementModel::HandleLocale;
        if(isset($fields[$locale])) {
            $element->$locale = $fields[$locale];
            unset($fields[$locale]);
        }
        
        // Set password
        $password = Import_ElementModel::HandlePassword;
        if(isset($fields[$password])) {
            $element->$password = $fields[$password];
            unset($fields[$password]);
        }
        
        // Return entry
        return $element;
                    
    }  

    public function save(UserModel &$element, $settings)
    {
        
        // Save user
        if(craft()->users->saveUser($element)) {
        
            // Assign to groups
            craft()->userGroups->assignUserToGroups($element->id, $settings['elementvars']['groups']);
        
            return true;
        
        }
        
        return false;
    
    }
    
    public function callback($fields, UserModel $element)
    {
        // No callback for users
    }

}