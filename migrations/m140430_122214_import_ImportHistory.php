<?php

namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName.
 */
class m140430_122214_import_ImportHistory extends BaseMigration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */
    public function safeUp()
    {

        // Create the craft_import_history table
        craft()->db->createCommand()->createTable('import_history', array(
            'userId' => array('column' => 'integer', 'required' => false),
            'sectionId' => array('column' => 'integer', 'required' => false),
            'entrytypeId' => array('column' => 'integer', 'required' => false),
            'file' => array('maxLength' => 255, 'column' => 'varchar'),
            'rows' => array('maxLength' => 11, 'decimals' => 0, 'unsigned' => false, 'length' => 10, 'column' => 'integer'),
            'behavior' => array('values' => array('append', 'replace', 'delete'), 'column' => 'enum'),
            'status' => array('values' => array('started', 'finished'), 'column' => 'enum'),
        ), null, true);

        // Add foreign keys to craft_import_history
        craft()->db->createCommand()->addForeignKey('import_history', 'userId', 'users', 'id', 'CASCADE', null);
        craft()->db->createCommand()->addForeignKey('import_history', 'sectionId', 'sections', 'id', 'CASCADE', null);
        craft()->db->createCommand()->addForeignKey('import_history', 'entrytypeId', 'entrytypes', 'id', 'CASCADE', null);

        // Create the craft_import_log table
        craft()->db->createCommand()->createTable('import_log', array(
            'historyId' => array('column' => 'integer', 'required' => false),
            'line' => array('maxLength' => 11, 'decimals' => 0, 'unsigned' => false, 'length' => 10, 'column' => 'integer'),
            'errors' => array('column' => 'text'),
        ), null, true);

        // Add foreign keys to craft_import_log
        craft()->db->createCommand()->addForeignKey('import_log', 'historyId', 'import_history', 'id', 'SET NULL', null);

        return true;
    }
}
