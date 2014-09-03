<?php
namespace Craft;

class ImportModel extends BaseModel 
{

    // Filetypes
    const TypeCSV     = 'text/csv';
    const TypeCSVWin  = 'text/comma-separated-values';
    const TypeCSVIE   = 'text/plain';
    const TypeCSVApp  = 'application/csv';
    const TypeCSVExc  = 'application/excel';
    const TypeCSVOff  = 'application/vnd.ms-excel';
    const TypeCSVOff2 = 'application/vnd.msexcel';
    const TypeCSVOth  = 'application/octet-stream';
    
    // Behaviors
    const BehaviorAppend  = 'append';
    const BehaviorReplace = 'replace';
    const BehaviorDelete  = 'delete';
    
    // Statuses
    const StatusStarted  = 'started';
    const StatusFinished = 'finished';
    const StatusReverted = 'reverted';
     
    // Handles
    const HandleTitle        = 'title';
    const HandleAuthor       = 'authorId';
    const HandlePostDate     = 'postDate';
    const HandleExpiryDate   = 'expiryDate';
    const HandleEnabled      = 'enabled';
    const HandleStatus       = 'status';
    const HandleSlug         = 'slug';
    const HandleParent       = 'parent';
    const HandleUsername     = 'username';
    const HandleFirstname    = 'firstName';
    const HandleLastname     = 'lastName';
    const HandleEmail        = 'email';
    
    // Fieldtypes
    const FieldTypeEntries    = 'Entries';
    const FieldTypeCategories = 'Categories';
    const FieldTypeAssets     = 'Assets';
    const FieldTypeUsers      = 'Users';
    const FieldTypeRichText   = 'RichText';
    const FieldTypeNumber     = 'Number';
    
    // Delimiters
    const DelimiterSemicolon = ';';
    const DelimiterComma     = ',';
    const DelimiterPipe      = '|';

    protected function defineAttributes() 
    {
        return array(
            'elementtype' => array(AttributeType::Enum, 'required' => true, 'label' => Craft::t('Element Type'), 'values' => array(ElementType::Entry, ElementType::User)),
            'section'     => array(AttributeType::Number, 'label' => Craft::t('Section')),
            'entrytype'   => array(AttributeType::Number, 'label' => Craft::t('Entrytype')),
            'groups'      => array(AttributeType::Mixed, 'label' => Craft::t('Groups')),
            'behavior'    => array(AttributeType::Enum, 'required' => true, 'label' => Craft::t('Behavior'), 'values' => array(self::BehaviorAppend, self::BehaviorReplace, self::BehaviorDelete)),
            'file'        => array(AttributeType::String, 'required' => true, 'label' => Craft::t('File')),
            'type'        => array(AttributeType::Enum, 'required' => true, 'label' => Craft::t('Filetype'), 'values' => array(self::TypeCSV, self::TypeCSVWin, self::TypeCSVIE, self::TypeCSVApp, self::TypeCSVExc, self::TypeCSVOff, self::TypeCSVOff2, self::TypeCSVOth)),
            'email'       => array(AttributeType::Bool, 'label' => Craft::t('Send e-mail notification')),
            'backup'      => array(AttributeType::Bool, 'label' => Craft::t('Backup Database'))
        );
    }
}
