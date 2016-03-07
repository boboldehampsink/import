<?php

namespace Craft;

/**
 * Import Log Record.
 *
 * Represents the import_log table
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 */
class Import_LogRecord extends BaseRecord
{
    /**
     * Return table name.
     *
     * @return string
     */
    public function getTableName()
    {
        return 'import_log';
    }

    /**
     * Return table attributes.
     *
     * @return array
     */
    protected function defineAttributes()
    {
        return array(
            'line' => AttributeType::Number,
            'errors' => AttributeType::Mixed,
        );
    }

    /**
     * Return table relations.
     *
     * @return array
     */
    public function defineRelations()
    {
        return array(
            'history' => array(static::BELONGS_TO, 'Import_HistoryRecord'),
        );
    }
}
