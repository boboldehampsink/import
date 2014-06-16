<?php
namespace Craft;

class Import_EntriesRecord extends BaseRecord
{

    public function getTableName()
    {
        return 'import_entries';
    }
    
    public function defineRelations()
    {
        return array(
            'history' => array(static::BELONGS_TO, 'Import_HistoryRecord'),
            'entry'   => array(static::BELONGS_TO, 'EntryRecord', 'onDelete' => static::CASCADE),
            'version' => array(static::BELONGS_TO, 'EntryVersionRecord', 'onDelete' => static::CASCADE)
        );
    }
    
}