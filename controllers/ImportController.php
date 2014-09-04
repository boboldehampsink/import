<?php
namespace Craft;

class ImportController extends BaseController 
{

    public function actionGetEntryTypes() 
    {
    
        // Only ajax post requests
        $this->requirePostRequest();
        $this->requireAjaxRequest();
        
        // Get section
        $section = craft()->request->getPost('section');
        $section = craft()->sections->getSectionById($section);
        
        // Get entry types
        $entrytypes = $section->getEntryTypes();
        
        // Return JSON
        $this->returnJson($entrytypes);
    
    }

    // Upload file and process it for mapping
    public function actionUpload() 
    {
        
        // Get import post
        $import = craft()->request->getRequiredPost('import');
    
        // Get file
        $file = \CUploadedFile::getInstanceByName('file');
        
        // Determine folder
        $folder = craft()->path->getStoragePath() . 'import/';
        
        // Ensure folder exists
        IOHelper::ensureFolderExists($folder);
        
        // Get filepath - save in storage folder
        $path = $folder . $file->getName();
 
        // Save file to Craft's temp folder for later use
        $file->saveAs($path);
        
        // Put vars in model
        $model           = new ImportModel();
        $model->filetype = $file->getType();
        
        // Validate filetype
        if($model->validate()) {
        
            // Get columns
            $columns = craft()->import->columns($path);
            
            // Send variables to template and display
            $this->renderTemplate('import/_map', array(
                'import'    => $import,
                'file'      => $path,
                'columns'   => $columns
            ));
        
        } else {
        
            // Not validated, show error
            craft()->userSession->setError(Craft::t('This filetype is not valid').': '.$model->filetype);
            
        }
    
    }
    
    // Start import task
    public function actionImport() 
    {
    
        // Get import post
        $settings = craft()->request->getRequiredPost('import');
        
        // Get file
        $file = craft()->request->getParam('file');
        
        // Get mapping fields
        $map = craft()->request->getParam('fields');
        $unique = craft()->request->getParam('unique');
        
        // Get rows/steps from file
        $rows = count(craft()->import->data($file));
        
        // Set more settings
        $settings = array_merge(array(
            'file'        => $file,
            'rows'        => $rows,
            'map'         => $map,
            'unique'      => $unique
        ), $settings);
        
        // Create history
        $history = craft()->import_history->start((object)$settings);

        // Create the import task
        $task = craft()->tasks->createTask('Import', Craft::t('Importing') . ' ' . basename($file), array_merge($settings, array('history' => $history)));
        
        // Notify user
        craft()->userSession->setNotice(Craft::t('Import process started.'));
        
        // Redirect to history
        $this->redirect('import/history?task=' . $task->id);
    
    }
    
}