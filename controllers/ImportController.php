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
    public function actionUploadFile() 
    {
    
        // Only post requests
        $this->requirePostRequest();
    
        // Get file
        $file = \CUploadedFile::getInstanceByName('importFile');
 
        // Save file to Craft's temp folder for later use
        $file->saveAs(craft()->path->getTempUploadsPath().$file->getName());
         
        // Get section
        $section = craft()->request->getPost('importSection');
        $entrytype = craft()->request->getPost('importEntryType');
        
        // Get behavior
        $behavior = craft()->request->getPost('importBehavior');
        
        // Send e-mail?
        $email = craft()->request->getPost('importEmail');
        
        // Backup?
        $backup = craft()->request->getPost('importBackup');
        
        // Put vars in model
        $import            = new ImportModel();
        $import->file      = craft()->path->getTempUploadsPath().$file->getName();
        $import->type      = $file->getType();
        $import->section   = $section;
        $import->entrytype = $entrytype;
        $import->behavior  = $behavior;
        $import->email     = $email;
        $import->backup    = $backup;
        
        // Validate model
        if($import->validate()) {
        
            // Get columns
            $columns = craft()->import->columns($import->file);
            
            // Send variables to template and display
            $this->renderTemplate('import/_map', array(
                'import'    => $import,
                'columns'   => $columns
            ));
        
        } else {
        
            // Not validated, show error
            craft()->userSession->setError(Craft::t('This filetype is not valid!').': '.$import->type);
            
        }
    
    }
    
    // Start import task
    public function actionImportFile() 
    {
    
        // Only post requests
        $this->requirePostRequest();
        
        // Get section
        $section = craft()->request->getParam('section');
        $entrytype = craft()->request->getParam('entrytype');
        
        // Get behavior
        $behavior = craft()->request->getParam('behavior');
        
        // Get file
        $file = craft()->request->getParam('file');
        
        // Email?
        $email = craft()->request->getParam('email');
        
        // Backup?
        $backup = craft()->request->getParam('backup');
        
        // Get mapping fields
        $map = craft()->request->getParam('fields');
        $unique = craft()->request->getParam('unique');
        
        // Get rows/steps from file
        $rows = count(craft()->import->data($file));
        
        // Define settings
        $settings = array(
            'file'      => $file,
            'rows'      => $rows,
            'map'       => $map,
            'unique'    => $unique,
            'section'   => $section,
            'entrytype' => $entrytype,
            'behavior'  => $behavior,
            'email'     => $email,
            'backup'    => $backup
        );
        
        // Create history
        $history = craft()->import_history->start((object)$settings);

        // Create the import task
        $task = craft()->tasks->createTask('Import', Craft::t('Importing') . ' ' . basename($file), array_merge($settings, array('history' => $history)));
        
        // Notify user
        craft()->userSession->setNotice(Craft::t('Import process started.'));
        
        // Redirect to index
        $this->redirect('import?task=' . $task->id);
    
    }
    
}