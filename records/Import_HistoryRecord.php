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
            'behavior'  => array(AttributeType::Enum, 'values' => array(ImportModel::BehaviorAppend, ImportModel::BehaviorReplace, ImportModel::BehaviorDelete)),
            'status'    => array(AttributeType::Enum, 'values' => array(ImportModel::StatusStarted, ImportModel::StatusFinished))
        );
    }
    
    public function defineRelations()
    {
        return array(
            'user'      => array(static::BELONGS_TO, 'UserRecord', 'onDelete' => static::CASCADE, 'required' => false),
            'section'   => array(static::BELONGS_TO, 'SectionRecord', 'onDelete' => static::CASCADE, 'required' => false),
            'entrytype' => array(static::BELONGS_TO, 'EntryTypeRecord', 'onDelete' => static::CASCADE, 'required' => false),
            'log'       => array(static::HAS_MANY, 'Import_LogRecord', 'logId')
        );
    }
    
}