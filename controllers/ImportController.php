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

            // Get source
            $source = craft()->assetSources->getSourceTypeById($import['assetsource']);

            // Get folder to save to
            $folderId = craft()->assets->getRootFolderBySourceId($import['assetsource']);

            // Move the file by source type implementation
            $response = $source->insertFileByPath($path, $folderId, $file->getName(), true);

            // Prevent sensitive information leak. Just in case.
            $response->deleteDataItem('filePath');

            // Put vars in model
            $model = new ImportModel();
            $model->filetype = $file->getType();

            // Validate filetype
            if ($model->validate()) {

                // Get columns
                $columns = craft()->import->columns($path);

                // Send variables to template and display
                $this->renderTemplate('import/_map', array(
                    'import' => $import,
                    'file' => $response->getDataItem('fileId'),
                    'columns' => $columns,
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
        $fileId = craft()->request->getParam('file');
        $file = craft()->assets->getFileById($fileId);

        // Get mapping fields
        $map = craft()->request->getParam('fields');
        $unique = craft()->request->getParam('unique');

        // Get rows/steps from file
        $rows = count(craft()->import->data($file->id));

        // Proceed when atleast one row
        if ($rows) {

            // Set more settings
            $settings = array_merge(array(
                'file' => $file->id,
                'rows' => $rows,
                'map' => $map,
                'unique' => $unique,
            ), $settings);

            // Create history
            $history = craft()->import_history->start($settings);

            // Add history to settings
            $settings['history'] = $history;

            // UNCOMMENT FOR DEBUGGING
            //craft()->import->debug($settings, $history, 1);

            // Create the import task
            $task = craft()->tasks->createTask('Import', Craft::t('Importing').' '.$file->filename, $settings);

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
