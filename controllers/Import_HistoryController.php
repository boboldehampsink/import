<?php

namespace Craft;

/**
 * Import History Controller.
 *
 * Request actions for Import History
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 */
class Import_HistoryController extends BaseController
{
    /**
     * Reverts an import.
     */
    public function actionRevert()
    {
        // If entry revisions are supported
        if (craft()->getEdition() == Craft::Pro) {

            // Get history id
            $history = craft()->request->getParam('id');

            // Set criteria
            $criteria = new \CDbCriteria();
            $criteria->condition = 'historyId = :history_id';
            $criteria->params = array(
                ':history_id' => $history,
            );

            // Get entries in history
            $entries = Import_EntriesRecord::model()->findAll($criteria);

            // Create the revert task
            $task = craft()->tasks->createTask('Import_Revert', Craft::t('Reverting import'), array(
                'entries' => $entries,
            ));

            // Notify user
            craft()->userSession->setNotice(Craft::t('Revert import process started.'));
        }

        // Redirect to history
        $this->redirect('import/history');
    }

    /**
     * Downloads an import file.
     *
     * @throws HttpException If not found
     */
    public function actionDownload()
    {

        // Get history id
        $history = craft()->request->getParam('id');

        // Get history
        $model = Import_HistoryRecord::model()->findById($history);

        // Get filepath
        $path = craft()->path->getStoragePath().'import/'.$history.'/'.$model->file;

        // Check if file exists
        if (file_exists($path)) {
            craft()->request->sendFile($model->file, IOHelper::getFileContents($path), array('forceDownload' => true));
        }

        // OR get file from cloud
        $asset = craft()->assets->getFileById($model->file);
        if ($asset) {
            $source = $asset->getSource();
            $sourceType = $source->getSourceType();
            $file = $sourceType->getLocalCopy($asset);

            // Send the file to the browser
            craft()->request->sendFile($asset->filename, IOHelper::getFileContents($file), array('forceDownload' => true));
        }

        // Not found, = 404
        throw new HttpException(404);
    }

    /**
     * Deletes an import from the history.
     */
    public function actionDelete()
    {

        // Get history id
        $history = craft()->request->getParam('id');

        // Get history
        $model = Import_HistoryRecord::model()->findById($history);

        // Notify user
        craft()->userSession->setNotice(Craft::t('The import history of {file} has been deleted.', array(
            'file' => $model->file,
        )));

        // Set criteria
        $criteria = new \CDbCriteria();
        $criteria->condition = 'historyId = :history_id';
        $criteria->params = array(
            ':history_id' => $history,
        );

        // Delete attached logs
        Import_LogRecord::model()->deleteAll($criteria);

        // Delete history
        $model->delete();

        // Redirect to history
        $this->redirect('import/history');
    }
}
