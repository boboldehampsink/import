<?php

namespace Craft;

/**
 * Import History Record.
 *
 * Represents the import_history table
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 */
class Import_HistoryRecord extends BaseRecord
{
    /**
     * Return table name.
     *
     * @return string
     */
    public function getTableName()
    {
        return 'import_history';
    }

    /**
     * Return table attributes.
     *
     * @return array
     */
    protected function defineAttributes()
    {
        return array(
            'type' => AttributeType::String,
            'file' => AttributeType::Name,
            'rows' => AttributeType::Number,
            'behavior' => array(AttributeType::Enum, 'values' => array(ImportModel::BehaviorAppend, ImportModel::BehaviorReplace, ImportModel::BehaviorDelete)),
            'status' => array(AttributeType::Enum, 'values' => array(ImportModel::StatusStarted, ImportModel::StatusFinished, ImportModel::StatusReverted)),
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
            'user' => array(static::BELONGS_TO, 'UserRecord', 'onDelete' => static::CASCADE, 'required' => false),
            'log' => array(static::HAS_MANY, 'Import_LogRecord', 'logId'),
        );
    }

    /**
     * Get real file name.
     *
     * @return string
     */
    public function getFilename()
    {
        if (is_numeric($this->file)) {
            return craft()->assets->getFileById($this->file)->filename;
        }

        return $this->file;
    }
}
