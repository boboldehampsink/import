<?php
namespace Craft;

class Import_HistoryController extends BaseController 
{

    public function actionRevert() 
    {
    
        // Get history id
        $history = craft()->request->getParam('history');
        
        // Set criteria
        $criteria = new \CDbCriteria;
        $criteria->condition = 'historyId = :history_id';
        $criteria->params = array(
            ':history_id' => $history,
        );
        
        // Get entries in history
        $entries = Import_EntriesRecord::model()->findAll($criteria);
        
        // Create the revert task
        $task = craft()->tasks->createTask('Import_Revert', Craft::t('Reverting import'), array(
            'entries' => $entries
        ));
        
        // Notify user
        craft()->userSession->setNotice(Craft::t('Revert import process started.'));
        
        // Redirect to index
        $this->redirect('import');
    
    }
    
}