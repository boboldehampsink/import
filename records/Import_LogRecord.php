<?php
namespace Craft;

class Import_LogRecord extends BaseRecord
{

    public function getTableName()
    {
        return 'import_log';
    }

    protected function defineAttributes()
    {
        return array(
            'line'     => AttributeType::Number,
            'errors'   => AttributeType::Mixed,
        );
    }

    public function defineRelations()
    {
        return array(
            'history'  => array(static::BELONGS_TO, 'Import_HistoryRecord'),
        );
    }
}
