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
    
    // Handles
    const HandleTitle        = 'title';
    const HandleAuthor       = 'authorId';
    const HandlePostDate     = 'postDate';
    const HandleExpiryDate   = 'expiryDate';
    const HandleEnabled      = 'enabled';
    const HandleStatus       = 'status';
    
    // Fieldtypes
    const FieldTypeEntries    = 'Entries';
    const FieldTypeCategories = 'Categories';
    const FieldTypeAssets     = 'Assets';
    const FieldTypeUsers      = 'Users';
    const FieldTypeRichText   = 'RichText';
    
    // Delimiters
    const DelimiterSemicolon = ';';
    const DelimiterComma     = ',';
    const DelimiterPipe      = '|';

	protected function defineAttributes()
	{
		return array(
		    'section'   => array(AttributeType::Number, 'required' => true, 'label' => Craft::t('Section')),
		    'entrytype' => array(AttributeType::Number, 'required' => true, 'label' => Craft::t('Entrytype')),
			'behavior'  => array(AttributeType::Enum, 'required' => true, 'values' => array(self::BehaviorAppend, self::BehaviorReplace, self::BehaviorDelete)),
		    'file'      => array(AttributeType::String, 'required' => true, 'label' => Craft::t('File')),
			'type'      => array(AttributeType::Enum, 'required' => true, 'values' => array(self::TypeCSV, self::TypeCSVWin, self::TypeCSVIE, self::TypeCSVApp, self::TypeCSVExc, self::TypeCSVOff, self::TypeCSVOff2, self::TypeCSVOth))
		);
	}
}
