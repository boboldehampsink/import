<?php

namespace Craft;

/**
 * Import Model.
 *
 * Contains common constants and file upload validation
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 */
class ImportModel extends BaseModel
{
    /**
     * Filetypes.
     */
    const TypeCSV = 'text/csv';
    const TypeCSVWin = 'text/comma-separated-values';
    const TypeCSVFF = 'text/x-comma-separated-values';
    const TypeCSVIE = 'text/plain';
    const TypeCSVApp = 'application/csv';
    const TypeCSVExc = 'application/excel';
    const TypeCSVOff = 'application/vnd.ms-excel';
    const TypeCSVOff2 = 'application/vnd.msexcel';
    const TypeCSVOth = 'application/octet-stream';

    /**
     * Backup.
     */
    const Backup = 'backup';

    /**
     * Behaviors.
     */
    const BehaviorAppend = 'append';
    const BehaviorReplace = 'replace';
    const BehaviorDelete = 'delete';

    /**
     * Statuses.
     */
    const StatusStarted = 'started';
    const StatusFinished = 'finished';
    const StatusReverted = 'reverted';

    /**
     * Fieldtypes.
     */
    const FieldTypeEntries = 'Entries';
    const FieldTypeCategories = 'Categories';
    const FieldTypeAssets = 'Assets';
    const FieldTypeUsers = 'Users';
    const FieldTypeRichText = 'RichText';
    const FieldTypeNumber = 'Number';
    const FieldTypeDate = 'Date';
    const FieldTypeCheckboxes = 'Checkboxes';
    const FieldTypeDropdown = 'Dropdown';
    const FieldTypeMultiSelect = 'MultiSelect';
    const FieldTypeRadioButtons = 'RadioButtons';
    const FieldTypeTags = 'Tags';
    const FieldTypeLightSwitch = 'Lightswitch';

    /**
     * Delimiters.
     */
    const DelimiterSemicolon = ';';
    const DelimiterComma = ',';
    const DelimiterPipe = '|';

    /**
     * Use model validation to validate filetype.
     *
     * @return array
     */
    protected function defineAttributes()
    {
        return array(
            'filetype' => array(AttributeType::Enum,
                'required' => true,
                'label' => Craft::t('Filetype'),
                'values' => array(
                    self::TypeCSV,
                    self::TypeCSVWin,
                    self::TypeCSVFF,
                    self::TypeCSVIE,
                    self::TypeCSVApp,
                    self::TypeCSVExc,
                    self::TypeCSVOff,
                    self::TypeCSVOff2,
                    self::TypeCSVOth,
                ),
            ),
        );
    }
}
