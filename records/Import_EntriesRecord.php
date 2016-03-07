<?php

namespace Craft;

/**
 * Import Entries Record.
 *
 * Represents the import_entries table
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 */
class Import_EntriesRecord extends BaseRecord
{
    /**
     * Return table name.
     *
     * @return string
     */
    public function getTableName()
    {
        return 'import_entries';
    }

    /**
     * Return table relations.
     *
     * @return array
     */
    public function defineRelations()
    {
        $relations = array(
            'history' => array(static::BELONGS_TO, 'Import_HistoryRecord'),
            'entry' => array(static::BELONGS_TO, 'EntryRecord', 'onDelete' => static::CASCADE),
        );

        // If entry revisions are supported
        if (craft()->getEdition() == Craft::Pro) {
            $relations['version'] = array(static::BELONGS_TO, 'EntryVersionRecord', 'onDelete' => static::CASCADE);
        }

        return $relations;
    }
}
