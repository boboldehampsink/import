<?php

namespace Craft;

/**
 * Import Controller.
 *
 * Request actions for importing
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 */
class ImportController extends BaseController
{
    /**
     * Get available entry types.
     */
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

    /**
     * Upload file and process it for mapping.
     */
    public function actionUpload()
    {

        // Get import post
        $import = craft()->request->getRequiredPost('import');

        // Get file
        $file = \CUploadedFile::getInstanceByName('file');

        // Is file valid?
        if (!is_null($file)) {

            // Determine folder
            $folder = craft()->path->getStoragePath().'import/';

            // Ensure folder exists
            IOHelper::ensureFolderExists($folder);

            // Get filepath - save in storage folder
            $path = $folder.$file->getName();

            // Save file to Craft's temp folder for later use
            $file->saveAs($path);

            // Put vars in model
            $model           = new ImportModel();
            $model->filetype = $file->getType();

            // Validate filetype
            if ($model->validate()) {

                // Get columns
                $columns = craft()->import->columns($path);

                // Send variables to template and display
                $this->renderTemplate('import/_map', array(
                    'import'    => $import,
                    'file'      => $path,
                    'columns'   => $columns,
                ));
            } else {

                // Not validated, show error
                craft()->userSession->setError(Craft::t('This filetype is not valid').': '.$model->filetype);
            }
        } else {

            // No file uploaded
            craft()->userSession->setError(Craft::t('Please upload a file.'));
        }
    }

    /**
     * Start import task.
     */
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

        // Proceed when atleast one row
        if ($rows) {

            // Set more settings
            $settings = array_merge(array(
                'file'        => $file,
                'rows'        => $rows,
                'map'         => $map,
                'unique'      => $unique,
            ), $settings);

            // Create history
            $history = craft()->import_history->start($settings);

            // Add history to settings
            $settings['history'] = $history;

            // UNCOMMENT FOR DEBUGGING
            //craft()->import->debug($settings, $history, 1);

            // Determine new folder to save original importfile
            $folder = dirname($file).'/'.$history.'/';
            IOHelper::ensureFolderExists($folder);

            // Move the file to its history folder
            IOHelper::move($file, $folder.basename($file));

            // Update the settings with the new file location
            $settings['file'] = $folder.basename($file);

            // Create the import task
            $task = craft()->tasks->createTask('Import', Craft::t('Importing').' '.basename($file), $settings);

            // Notify user
            craft()->userSession->setNotice(Craft::t('Import process started.'));

            // Redirect to history
            $this->redirect(UrlHelper::getCpUrl('import/history', array('task' => $task->id)));
        } else {

            // Redirect to history
            $this->redirect(UrlHelper::getCpUrl('import/history'));
        }
    }
}
