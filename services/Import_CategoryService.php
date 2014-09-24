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
        
        // Set parent or ancestors
        $parent = Import_ElementModel::HandleParent;
        $ancestors = Import_ElementModel::HandleAncestors;
        
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
        
        } elseif(isset($fields[$ancestors])) {
                   
           // Get data
           $data = $fields[$ancestors];
            
            // Fresh up $data
           $data = str_replace("\n", "", $data);
           $data = str_replace("\r", "", $data);
           $data = trim($data);
           
           // Don't connect empty fields
           if(!empty($data)) {
         
               // Get category data
               $category = new CategoryModel();
               $category->groupId = $entry->groupId;                  
           
               // This we append before the slugified path
               $categoryUrl = str_replace('{slug}', '', $category->getUrlFormat());
                                                   
               // Find matching element by URI (dirty, not all categories have URI's)        
               $criteria = craft()->elements->getCriteria(ElementType::Category);
               $criteria->groupId = $entry->groupId;
               $criteria->uri = $categoryUrl . craft()->import->slugify($data);
               $criteria->limit = 1;
               
               // Return the first found element for connecting
               if($criteria->total()) {
               
                   $entry->$parent = $criteria->first()->id;
                   
               }
           
           }
           
           unset($fields[$ancestors]);
        
        }
        
        // Return entry
        return $entry;
                    
    }

}