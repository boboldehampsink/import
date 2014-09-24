<?php
namespace Craft;

class Import_CategoryService extends BaseApplicationComponent 
{

    public function getGroups()
    {
    
        // Return editable groups for user
        return craft()->categories->getEditableGroups();
    
    }

    public function setModel($settings)
    {
    
        // Set up new category model
        $entry = new CategoryModel();
        $entry->groupId = $settings['elementvars']['group'];
        
        return $entry;    
    
    }
    
    public function setCriteria($settings)
    {
    
        // Match with current data
        $criteria = craft()->elements->getCriteria(ElementType::Category);
        $criteria->limit = null;
        $criteria->status = isset($settings['map']['status']) ? $settings['map']['status'] : null;
    
        // Look in same group when replacing
        $criteria->groupId = $settings['elementvars']['group'];
    
        return $criteria;
    
    }
    
    public function delete($elements)
    {
    
        // Delete categories
        return craft()->categories->deleteCategory($elements);
    
    }
    
    public function save(&$element, $settings)
    {
        
        // Save category
        return craft()->categories->saveCategory($element);
    
    }
    
    // Prepare reserved ElementModel values
    public function prepForElementModel(&$fields, CategoryModel $entry) 
    {
    
        // Set slug
        $slug = Import_ElementModel::HandleSlug;
        if(isset($fields[$slug])) {
            $entry->$slug = ElementHelper::createSlug($fields[$slug]);
            unset($fields[$slug]);
        }
    
        // Set title
        $title = Import_ElementModel::HandleTitle;
        if(isset($fields[$title])) {
            $entry->getContent()->$title = $fields[$title];
            unset($fields[$title]);
        }
        
        // Set parent id
        $parent = Import_ElementModel::HandleParent;
        if(isset($fields[$parent])) {
           
           // Get data
           $data = $fields[$parent];
            
            // Fresh up $data
           $data = str_replace("\n", "", $data);
           $data = str_replace("\r", "", $data);
           $data = trim($data);
           
           // Don't connect empty fields
           if(!empty($data)) {
         
               // Find matching element       
               $criteria = craft()->elements->getCriteria(ElementType::Category);
               $criteria->groupId = $entry->groupId;

               // Exact match
               $criteria->search = '"'.$data.'"';
               
               // Return the first found element for connecting
               if($criteria->total()) {
               
                   $entry->$parent = $criteria->first()->id;
                   
               }
           
           }
           
           unset($fields[$parent]);
        
        }
        
        // Return entry
        return $entry;
                    
    }

}