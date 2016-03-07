<?php

namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName.
 */
class m140616_080724_import_saveEntryIdAndVersion extends BaseMigration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */
    public function safeUp()
    {

        // Create the craft_import_entries table
        craft()->db->createCommand()->createTable('import_entries', array(
            'historyId' => array('column' => 'integer', 'required' => false),
            'entryId' => array('column' => 'integer', 'required' => false),
            'versionId' => array('column' => 'integer', 'required' => false),
        ), null, true);

        // Add foreign keys to craft_import_entries
        craft()->db->createCommand()->addForeignKey('import_entries', 'historyId', 'import_history', 'id', 'SET NULL', null);
        craft()->db->createCommand()->addForeignKey('import_entries', 'entryId', 'entries', 'id', 'CASCADE', null);
        craft()->db->createCommand()->addForeignKey('import_entries', 'versionId', 'entryversions', 'id', 'CASCADE', null);

        return true;
    }
}
