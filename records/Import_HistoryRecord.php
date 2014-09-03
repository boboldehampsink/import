<?php
namespace Craft;

class Import_HistoryRecord extends BaseRecord
{

    public function getTableName()
    {
        return 'import_history';
    }

    protected function defineAttributes()
    {
        return array(
            'elementtype' => AttributeType::String,
            'file'        => AttributeType::Name,
            'rows'        => AttributeType::Number,
            'behavior'    => array(AttributeType::Enum, 'values' => array(ImportModel::BehaviorAppend, ImportModel::BehaviorReplace, ImportModel::BehaviorDelete)),
            'status'      => array(AttributeType::Enum, 'values' => array(ImportModel::StatusStarted, ImportModel::StatusFinished, ImportModel::StatusReverted))
        );
    }
    
    public function defineRelations()
    {
        return array(
            'user' => array(static::BELONGS_TO, 'UserRecord',       'onDelete' => static::CASCADE, 'required' => false),
            'log'  => array(static::HAS_MANY,   'Import_LogRecord', 'logId')
        );
    }
    
}