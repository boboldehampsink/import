<?php

namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName.
 */
class m140903_075432_import_ImportElements extends BaseMigration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */
    public function safeUp()
    {

        // Alter the craft_import_history table

        // Drop sectionId column
        craft()->db->createCommand()->dropForeignKey('import_history', 'sectionId');
        craft()->db->createCommand()->dropColumn('import_history', 'sectionId');

        // Drop entrytypeId column
        craft()->db->createCommand()->dropForeignKey('import_history', 'entrytypeId');
        craft()->db->createCommand()->dropColumn('import_history', 'entrytypeId');

        // Add elementtype column
        craft()->db->createCommand()->addColumnAfter('import_history', 'type', ColumnType::Varchar, 'userId');

        // Fill type column by default
        //craft()->db->createCommand()->update('import_history', array('type' => ElementType::Entry));
        // ^^ we're not doing this as it affects the dateUpdated table

        return true;
    }
}
