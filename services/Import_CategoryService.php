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
    
    public function delete($ids)
    {
    
        // Delete categories
        craft()->categories->deleteCategory($ids);
    
    }
    
    public function save(&$element, $settings)
    {
        
        // Save category
        return craft()->categories->saveCategory($element);
    
    }
    
    // Prepare reserved ElementModel values
    public function prepForElementModel(&$fields, CategoryModel $entry) 
    {
        
        // Return entry
        return $entry;
                    
    }

}