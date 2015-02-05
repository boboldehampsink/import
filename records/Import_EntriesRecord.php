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
        $relations = array(
            'history' => array(static::BELONGS_TO, 'Import_HistoryRecord'),
            'entry'   => array(static::BELONGS_TO, 'EntryRecord', 'onDelete' => static::CASCADE)
        );

        // If entry revisions are supported
        if (craft()->getEdition() == Craft::Pro)
        {
            $relations['version'] = array(static::BELONGS_TO, 'EntryVersionRecord', 'onDelete' => static::CASCADE);
        }

        return $relations;
    }
    
}