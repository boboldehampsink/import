<?php

namespace Craft;

use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Contains unit tests for the ImportService.
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 *
 * @coversDefaultClass Craft\ImportService
 * @covers ::<!public>
 */
class ImportServiceTest extends BaseTest
{
    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        // Set up parent
        parent::setUpBeforeClass();

        // Require dependencies
        require_once __DIR__.'/../services/ImportService.php';
        require_once __DIR__.'/../services/Import_HistoryService.php';
        require_once __DIR__.'/../services/IImportElementType.php';
        require_once __DIR__.'/../services/Import_EntryService.php';
        require_once __DIR__.'/../models/ImportModel.php';
    }

    /**
     * Setup mock localization service.
     */
    public function setUp()
    {
        $this->setMockLocalizationService();
    }

    /**
     * @covers ::columns
     */
    public function testColumnsShouldReturnColumnRow()
    {
        $file = __DIR__.'/tst_csv.csv';
        $expectedColumns = array('column1', 'column2', 'column3', 'column4', 'column5');

        $this->setMockAssetsService($file);

        $service = new ImportService();
        $result = $service->columns($file);

        $this->assertSame($expectedColumns, $result);
    }

    /**
     * @covers ::data
     */
    public function testDataShouldReturnDataRows()
    {
        $file = __DIR__.'/tst_csv.csv';
        $expectedData = array(
            array('row1value1', 'row1value2', 'row1value3', 'row1value4', 'row1value5'),
            array('row1value1', 'row2value2', 'row3value3', 'row4value4', 'row5value5'),
        );

        $this->setMockAssetsService($file);

        $service = new ImportService();
        $result = $service->data($file);

        $this->assertSame($expectedData, $result);
    }

    /**
     * @covers ::row
     */
    public function testRowShouldLogErrorWhenColumnsAndDataDoNotMatch()
    {
        $row = 1;
        $historyId = 2;
        $settings = array(
            'map' => array('column1', 'column2', 'column3'),
            'history' => $historyId,
        );
        $data = array('row1value1', 'row2', 'value2', 'row3value3');
        $message = array(array(Craft::t('Columns and data did not match, could be due to malformed CSV row.')));

        $this->setMockImportHistoryService($historyId, $row, $message);

        $service = new ImportService();
        $service->row($row, $data, $settings);
    }

    /**
     * @covers ::row
     * @covers ::getService
     *
     * @expectedException Exception
     * @expectedExceptionMessage Unknown Element Type Service called.
     */
    public function testRowShouldThrowExceptionWhenTypeUnknown()
    {
        $row = 1;
        $settings = array(
            'map' => array(),
            'type' => 'TypeDoesNotExists',
        );
        $data = array();

        $service = new ImportService();
        $service->row($row, $data, $settings);
    }

    /**
     * @covers ::row
     * @covers ::getService
     */
    public function testRowShouldLogErrorWhenSaveFails()
    {
        $row = 1;
        $historyId = 2;
        $settings = array(
            'map' => array(),
            'type' => 'TypeExists',
            'unique' => false,
            'history' => $historyId,
        );

        $data = array();
        $fields = array_combine($settings['map'], $data);

        $mockEntry = $this->getMockEntry();
        $mockEntry->expects($this->exactly(1))->method('getErrors')->willReturn(array());
        $mockEntry->expects($this->exactly(1))->method('setContentFromPost')->with($fields);
        $this->setMockImportEntryService($settings, $mockEntry, $fields, false);
        $this->setMockImportHistoryService($historyId, $row, $this->isType('array'));

        $service = new ImportService();
        $service->row($row, $data, $settings);
    }

    /**
     * @covers ::row
     * @covers ::getService
     */
    public function testRowShouldCallCallbackWhenSaveSucceeds()
    {
        $row = 1;
        $historyId = 2;
        $settings = array(
            'map' => array('field1', 'field2', 'dont'),
            'type' => 'TypeExists',
            'unique' => false,
            'history' => $historyId,
        );

        $data = array('settings1' => array(), 'settings2' => array(), 'dont' => array());
        $fields = array_combine($settings['map'], $data);
        unset($fields['dont']);

        $mockEntry = $this->getMockEntry();
        $mockEntry->expects($this->exactly(1))->method('setContentFromPost')->with($fields);
        $mockImportEntryService = $this->setMockImportEntryService($settings, $mockEntry, $fields, true);
        $mockImportEntryService->expects($this->exactly(1))->method('callback')->with($fields, $mockEntry);

        $service = new ImportService();
        $service->row($row, $data, $settings);
    }

    /**
     * @covers ::row
     * @covers ::getService
     */
    public function testRowShouldCatchExceptions()
    {
        $row = 1;
        $historyId = 2;
        $settings = array(
            'map' => array('field1', 'field2'),
            'type' => 'TypeExists',
            'unique' => false,
            'history' => $historyId,
        );

        $mockException = $this->getMock('Craft\Exception');
        $data = array('settings1' => array(), 'settings2' => array());
        $fields = array_combine($settings['map'], $data);

        $mockEntry = $this->getMockEntry();
        $mockEntry->expects($this->exactly(1))->method('setContentFromPost')->with($fields);
        $mockImportEntryService = $this->setMockImportEntryService($settings, $mockEntry, $fields);
        $mockImportEntryService->expects($this->exactly(1))->method('save')->willThrowException($mockException);
        $this->setMockImportHistoryService($historyId, $row, $this->isType('array'));

        $mockPluginsService = $this->getMock('Craft\PluginsService');
        $mockPluginsService->expects($this->any())->method('call')->willReturnCallback(
            function ($method) use ($mockException) {
                if ($method == 'registerImportService') {
                    return;
                } else {
                    throw $mockException;
                }
            }
        );
        $this->setComponent(craft(), 'plugins', $mockPluginsService);

        $service = new ImportService();
        $service->row($row, $data, $settings);
    }

    /**
     * @covers ::row
     * @covers ::getService
     */
    public function testRowUniqueReplaceOrDeleteShouldLogErrorWhenFieldValueEmpty()
    {
        $row = 1;
        $historyId = 2;
        $settings = array(
            'user' => 1,
            'map' => array('field1' => 'field1', 'field2' => 'field2'),
            'type' => 'TypeExists',
            'unique' => array('field1' => 1, 'field2' => 0),
            'history' => $historyId,
        );

        $data = array('settings1' => '', 'settings2' => '');
        $fields = array_combine($settings['map'], $data);

        $mockEntry = $this->getMockEntry();
        $mockCriteria = $this->getMockCriteria();
        $mockImportEntryService = $this->setMockImportEntryService($settings, $mockEntry, $fields);
        $mockImportEntryService->expects($this->exactly(1))->method('setCriteria')
            ->with($settings)->willReturn($mockCriteria);
        $this->setMockImportHistoryService($historyId, $row, $this->isType('array'));

        $service = new ImportService();
        $service->row($row, $data, $settings);
    }

    /**
     * @covers ::row
     * @covers ::getService
     */
    public function testRowUniqueReplaceOrDeleteShouldDoNothingWhenNoResultFound()
    {
        $row = 1;
        $historyId = 2;
        $settings = array(
            'user' => 1,
            'map' => array('field1' => 'field1', 'field2' => 'field2'),
            'type' => 'TypeExists',
            'unique' => array('field1' => 1, 'field2' => 0),
            'history' => $historyId,
        );

        $data = array('settings1' => 'value1', 'settings2' => 'value2');
        $fields = array_combine($settings['map'], $data);

        $mockEntry = $this->getMockEntry();
        $mockCriteria = $this->getMockCriteria();
        $mockCriteria->expects($this->exactly(1))->method('count')->willReturn(0);

        $mockImportEntryService = $this->setMockImportEntryService($settings, $mockEntry, $fields);
        $mockImportEntryService->expects($this->exactly(1))->method('setCriteria')
            ->with($settings)->willReturn($mockCriteria);

        $service = new ImportService();
        $service->row($row, $data, $settings);
    }

    /**
     * @covers ::row
     * @covers ::getService
     *
     * @expectedException Exception
     * @expectedExceptionMessage Tried to import without permission
     */
    public function testRowUniqueReplaceOrDeleteShouldThrowExceptionWhenPermissionDenied()
    {
        $row = 1;
        $historyId = 2;
        $settings = array(
            'user' => 1,
            'map' => array('field1' => 'field1', 'field2' => 'field2'),
            'type' => 'TypeExists',
            'unique' => array('field1' => 1, 'field2' => 0),
            'history' => $historyId,
        );

        $data = array('settings1' => 'value1', 'settings2' => 'value2');
        $fields = array_combine($settings['map'], $data);

        $mockEntry = $this->getMockEntry();
        $mockCriteria = $this->getMockCriteria();
        $mockCriteria->expects($this->exactly(1))->method('count')->willReturn(1);

        $mockImportEntryService = $this->setMockImportEntryService($settings, $mockEntry, $fields);
        $mockImportEntryService->expects($this->exactly(1))->method('setCriteria')
            ->with($settings)->willReturn($mockCriteria);
        $this->setMockImportHistoryService($historyId, $row, $this->isType('array'));

        $mockUser = $this->getMockUser();
        $this->setMockUsersService($mockUser);

        $service = new ImportService();
        $service->row($row, $data, $settings);
    }

    /**
     * @covers ::row
     * @covers ::getService
     */
    public function testRowUniqueReplaceOrDeleteShouldFindExistingElement()
    {
        $row = 1;
        $historyId = 2;
        $settings = array(
            'user' => 1,
            'map' => array('field1' => 'field1', 'field2' => 'field2'),
            'type' => 'TypeExists',
            'unique' => array('field1' => 1, 'field2' => 0),
            'history' => $historyId,
        );

        $data = array('settings1' => 'value1', 'settings2' => 'value2');
        $fields = array_combine($settings['map'], $data);

        $mockEntry = $this->getMockEntry();
        $mockCriteria = $this->getMockCriteria();
        $mockCriteria->expects($this->exactly(1))->method('count')->willReturn(1);
        $mockCriteria->expects($this->exactly(1))->method('first')->willReturn($mockEntry);

        $mockImportEntryService = $this->setMockImportEntryService($settings, $mockEntry, $fields, true);
        $mockImportEntryService->expects($this->exactly(1))->method('setCriteria')
            ->with($settings)->willReturn($mockCriteria);
        $this->setMockImportHistoryService($historyId, $row, $this->isType('array'));

        $mockUser = $this->getMockUser();
        $mockUser->expects($this->exactly(2))->method('can')->willReturnMap(array(
            array('delete', false),
            array('append', true),
        ));
        $this->setMockUsersService($mockUser);

        $service = new ImportService();
        $service->row($row, $data, $settings);
    }

    /**
     * @covers ::row
     * @covers ::getService
     */
    public function testRowUniqueReplaceOrDeleteShouldDeleteExistingElement()
    {
        $row = 1;
        $historyId = 2;
        $settings = array(
            'user' => 1,
            'map' => array('field1' => 'field1', 'field2' => 'field2'),
            'type' => 'TypeExists',
            'unique' => array('field1' => 1, 'field2' => 0),
            'history' => $historyId,
            'behavior' => ImportModel::BehaviorDelete,
        );

        $data = array('settings1' => 'value1', 'settings2' => 'value2');
        $fields = array_combine($settings['map'], $data);

        $mockEntry = $this->getMockEntry();
        $mockCriteria = $this->getMockCriteria();
        $mockCriteria->expects($this->exactly(1))->method('count')->willReturn(1);
        $mockCriteria->expects($this->exactly(1))->method('find')->willReturn(array($mockEntry));

        $mockImportEntryService = $this->setMockImportEntryService($settings, $mockEntry, $fields);
        $mockImportEntryService->expects($this->exactly(1))->method('setCriteria')
            ->with($settings)->willReturn($mockCriteria);
        $this->setMockImportHistoryService($historyId, $row, $this->isType('array'));

        $mockUser = $this->getMockUser();
        $mockUser->expects($this->exactly(1))->method('can')->willReturnMap(array(
            array('delete', true),
        ));
        $this->setMockUsersService($mockUser);

        /** @var ImportService $service */
        $service = $this->getMock('Craft\ImportService', array('onBeforeImportDelete'));
        $service->row($row, $data, $settings);
    }

    /**
     * @covers ::row
     * @covers ::getService
     */
    public function testRowUniqueReplaceOrDeleteShouldLogErrorWhenDeleteFails()
    {
        $row = 1;
        $historyId = 2;
        $settings = array(
            'user' => 1,
            'map' => array('field1' => 'field1', 'field2' => 'field2'),
            'type' => 'TypeExists',
            'unique' => array('field1' => 1, 'field2' => 0),
            'history' => $historyId,
            'behavior' => ImportModel::BehaviorDelete,
        );

        $data = array('settings1' => 'value1', 'settings2' => 'value2');
        $fields = array_combine($settings['map'], $data);

        $mockEntry = $this->getMockEntry();
        $mockException = $this->getMock('Craft\Exception');
        $mockCriteria = $this->getMockCriteria();
        $mockCriteria->expects($this->exactly(1))->method('count')->willReturn(1);
        $mockCriteria->expects($this->exactly(1))->method('find')->willReturn(array($mockEntry));

        $mockImportEntryService = $this->setMockImportEntryService($settings, $mockEntry, $fields);
        $mockImportEntryService->expects($this->exactly(1))->method('setCriteria')
            ->with($settings)->willReturn($mockCriteria);
        $mockImportEntryService->expects($this->exactly(1))->method('delete')->willThrowException($mockException);
        $this->setMockImportHistoryService($historyId, $row, $this->isType('array'));

        $mockUser = $this->getMockUser();
        $mockUser->expects($this->exactly(1))->method('can')->willReturnMap(array(
            array('delete', true),
        ));
        $this->setMockUsersService($mockUser);

        /** @var ImportService $service */
        $service = $this->getMock('Craft\ImportService', array('onBeforeImportDelete'));
        $service->row($row, $data, $settings);
    }

    /**
     * @covers ::finish
     */
    public function testFinishShouldMailResults()
    {
        $historyId = 1;
        $settings = array(
            'user' => 1,
            'history' => $historyId,
            'email' => true,
            'rows' => 1,
        );

        $mockImportHistoryService = $this->getMock('Craft\Import_HistoryService');
        $mockImportHistoryService->expects($this->any())->method('end')->with($historyId, ImportModel::StatusFinished);
        $this->setComponent(craft(), 'import_history', $mockImportHistoryService);

        $mockEmailService = $this->getMock('Craft\EmailService');
        $mockEmailService->expects($this->exactly(1))->method('sendEmail')->with($this->isInstanceOf('Craft\EmailModel'));
        $this->setComponent(craft(), 'email', $mockEmailService);

        $mockTwigEnvironment = $this->getMockBuilder('Craft\TwigEnvironment')
            ->disableOriginalConstructor()
            ->getMock();
        $mockTemplatesService = $this->getMock('Craft\TemplatesService');
        $mockTemplatesService->expects($this->exactly(1))->method('render')->willReturn('renderedtemplate');
        $mockTemplatesService->expects($this->exactly(1))->method('getTwig')->willReturn($mockTwigEnvironment);
        $this->setComponent(craft(), 'templates', $mockTemplatesService);

        $mockUser = $this->getMockUser();
        $this->setMockUsersService($mockUser);

        $service = new ImportService();
        $service->log = array(1 => 'Error message');
        $service->finish($settings, false);
    }

    /**
     * Test preparing value for field type.
     *
     * @param string $fieldType
     * @param string $data
     * @param array  $settingsMap
     * @param string $criteria
     * @param string $expectedResult
     *
     * @covers ::prepForFieldType
     * @dataProvider provideValidFieldTypeData
     */
    public function testPrepForFieldType($fieldType, $data, array $settingsMap, $criteria, $expectedResult)
    {
        $fieldHandle = 'handle';

        $mockField = $this->getMockBuilder('Craft\FieldModel')
            ->disableOriginalConstructor()
            ->getMock();
        $mockField->expects($this->any())->method('__get')->willReturnMap(array(
            array('type', $fieldType),
        ));

        $this->setMockFieldsService($fieldHandle, $mockField);

        if (!empty($settingsMap)) {
            $this->setMockSettings($settingsMap, $mockField);
        }

        if (!empty($criteria)) {
            $this->setMockElementsServiceForFieldType($criteria);
        }

        $service = $this->getMock('Craft\ImportService', array('getNewTagModel'));

        if ($fieldType == ImportModel::FieldTypeTags) {
            $mockTag = $this->getMockTag();
            $mockTag->expects($this->any())->method('__get')->with('id')->willReturn($expectedResult[0]);
            $this->setMockTagsService($mockTag);
            $service->expects($this->any())->method('getNewTagModel')->willReturn($mockTag);
        }

        if ($fieldType == ImportModel::FieldTypeCategories) {
            $this->setMockCategoriesService();
        }

        $result = $service->prepForFieldType($data, $fieldHandle);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @covers ::getCustomOption
     */
    public function testGetCustomOptionShouldReturnFalseWhenNoCustomOptionFound()
    {
        $fieldHandle = 'handle';

        $mockPluginsService = $this->getMock('Craft\PluginsService');
        $mockPluginsService->expects($this->any())->method('call')
            ->with('registerImportOptionPaths')->willReturn(array());
        $this->setComponent(craft(), 'plugins', $mockPluginsService);

        $service = new ImportService();
        $result = $service->getCustomOption($fieldHandle);

        $this->assertFalse($result);
    }

    /**
     * @covers ::getCustomOption
     */
    public function testGetCustomOptionShouldReturnOptionWhenFound()
    {
        $fieldHandle = 'handle';
        $option = array('optionkey' => 'optionvalue');

        $mockPluginsService = $this->getMock('Craft\PluginsService');
        $mockPluginsService->expects($this->any())->method('call')
            ->with('registerImportOptionPaths')->willReturn(array(array($fieldHandle => $option)));
        $this->setComponent(craft(), 'plugins', $mockPluginsService);

        $service = new ImportService();
        $result = $service->getCustomOption($fieldHandle);

        $this->assertSame($option, $result);
    }

    /**
     * @covers ::slugify
     */
    public function testSlugifyShouldSlugifyString()
    {
        $string = 'Test string';
        $slug = 'test-string';

        $mockConfigService = $this->getMock('Craft\ConfigService');
        $mockConfigService->expects($this->any())->method('get')->willReturnCallback(
            function ($option) {
                if ($option == 'allowUppercaseInSlug') {
                    return false;
                } elseif ($option == 'slugWordSeparator') {
                    return '-';
                }
            }
        );
        $this->setComponent(craft(), 'config', $mockConfigService);

        $service = new ImportService();
        $result = $service->slugify($string);

        $this->assertSame($slug, $result);
    }

    /**
     * @return array
     */
    public function provideValidFieldTypeData()
    {
        require_once __DIR__.'/../models/ImportModel.php';

        return array(
            'Entries' => array(
                'fieldType' => ImportModel::FieldTypeEntries,
                'data' => 'asset1',
                'settings' => array(
                    'sources' => array(
                        'section:1',
                    ),
                ),
                'criteria' => array(
                    'elementType' => ElementType::Entry,
                    'methods' => array(
                        'ids' => array(1, 2, 3),
                    ),
                ),
                'result' => array(1, 2, 3),
            ),
            'Empty Entries' => array(
                'fieldType' => ImportModel::FieldTypeEntries,
                'data' => '',
                'settings' => array(),
                'criteria' => false,
                'result' => array(),
            ),
            'Categories' => array(
                'fieldType' => ImportModel::FieldTypeCategories,
                'data' => 'asset1',
                'settings' => array(
                    'source' => 'group:1',
                ),
                'criteria' => array(
                    'elementType' => ElementType::Category,
                    'methods' => array(
                        'ids' => array(1, 2, 3),
                    ),
                ),
                'result' => array(1, 2, 3),
            ),
            'Empty Categories' => array(
                'fieldType' => ImportModel::FieldTypeCategories,
                'data' => '',
                'settings' => array(),
                'criteria' => false,
                'result' => array(),
            ),
            'Assets' => array(
                'fieldType' => ImportModel::FieldTypeAssets,
                'data' => 'asset1',
                'settings' => array(
                    'sources' => array(
                        'folder:1',
                    ),
                ),
                'criteria' => array(
                    'elementType' => ElementType::Asset,
                    'methods' => array(
                        'ids' => array(1, 2, 3),
                    ),
                ),
                'result' => array(1, 2, 3),
            ),
            'Empty Assets' => array(
                'fieldType' => ImportModel::FieldTypeAssets,
                'data' => '',
                'settings' => array(),
                'criteria' => false,
                'result' => array(),
            ),
            'Users' => array(
                'fieldType' => ImportModel::FieldTypeUsers,
                'data' => 'user1',
                'settings' => array(
                    'sources' => array(
                        'group:1',
                    ),
                ),
                'criteria' => array(
                    'elementType' => ElementType::User,
                    'methods' => array(
                        'ids' => array(1, 2, 3),
                    ),
                ),
                'result' => array(1, 2, 3),
            ),
            'Empty Users' => array(
                'fieldType' => ImportModel::FieldTypeUsers,
                'data' => '',
                'settings' => array(),
                'criteria' => false,
                'result' => array(),
            ),
            'Existing tags' => array(
                'fieldType' => ImportModel::FieldTypeTags,
                'data' => 'tag1',
                'settings' => array(
                    'source' => 'group:1',
                ),
                'criteria' => array(
                    'elementType' => ElementType::Tag,
                    'methods' => array(
                        'total' => 1,
                        'ids' => array(1, 2, 3),
                    ),
                ),
                'result' => array(1, 2, 3),
            ),
            'New tags' => array(
                'fieldType' => ImportModel::FieldTypeTags,
                'data' => 'tag1',
                'settings' => array(
                    'source' => 'group:1',
                ),
                'criteria' => array(
                    'elementType' => ElementType::Tag,
                    'methods' => array(
                        'total' => 0,
                    ),
                ),
                'result' => array(1),
            ),
            'Number field' => array(
                'fieldType' => ImportModel::FieldTypeNumber,
                'data' => '4,5.3200',
                'settings' => array(),
                'criteria' => false,
                'result' => '45.32',
            ),
            'Date Field' => array(
                'fieldType' => ImportModel::FieldTypeDate,
                'data' => '12-12-2012',
                'settings' => array(),
                'criteria' => false,
                'result' => '2012-12-12 00:00:00',
            ),
            'Drop Down' => array(
                'fieldType' => ImportModel::FieldTypeDropdown,
                'data' => 'label',
                'settings' => array(
                    'options' => array(
                        'option' => array(
                            'label' => 'label',
                            'value' => 'optionvalue',
                        ),
                        'option2' => array(
                            'label' => 'label2',
                            'value' => 'value2',
                        ),
                    ),
                ),
                'criteria' => false,
                'result' => 'optionvalue',
            ),
            'MultiSelect' => array(
                'fieldType' => ImportModel::FieldTypeMultiSelect,
                'data' => '1,2,3,4',
                'settings' => array(),
                'criteria' => false,
                'result' => array('1', '2', '3', '4'),
            ),
            'LightSwitch yes' => array(
                'fieldType' => ImportModel::FieldTypeLightSwitch,
                'data' => 'Yes',
                'settings' => array(),
                'criteria' => false,
                'result' => true,
            ),
            'LightSwitch no' => array(
                'fieldType' => ImportModel::FieldTypeLightSwitch,
                'data' => 'No',
                'settings' => array(),
                'criteria' => false,
                'result' => false,
            ),
        );
    }

    /**
     * @param int    $historyId
     * @param int    $row
     * @param string $message
     */
    private function setMockImportHistoryService($historyId, $row, $message)
    {
        $mockImportHistoryService = $this->getMock('Craft\Import_HistoryService');
        $mockImportHistoryService->expects($this->any())->method('log')->with($historyId, $row, $message);
        $this->setComponent(craft(), 'import_history', $mockImportHistoryService);
    }

    /**
     * @param array      $settings
     * @param MockObject $mockEntry
     * @param array      $fields
     * @param bool       $saveSuccess
     *
     * @return MockObject|Import_EntryService
     */
    private function setMockImportEntryService(array $settings, MockObject $mockEntry, array $fields, $saveSuccess = null)
    {
        $mockImportTypeService = $this->getMock('Craft\Import_EntryService');
        $mockImportTypeService->expects($this->exactly(1))->method('setModel')->with($settings)->willReturn($mockEntry);
        $mockImportTypeService->expects($this->any())->method('prepForElementModel')
            ->with($fields, $mockEntry)->willReturn($mockEntry);
        if (!is_null($saveSuccess)) {
            $mockImportTypeService->expects($this->exactly(1))->method('save')
                ->with($mockEntry, $settings)->willReturn($saveSuccess);
        }
        $this->setComponent(craft(), 'import_typeexists', $mockImportTypeService);

        return $mockImportTypeService;
    }

    /**
     * @param MockObject $mockUser
     */
    private function setMockUserSession(MockObject $mockUser)
    {
        $mockUserSession = $this->getMock('Craft\UserSessionService');
        $this->setComponent(craft(), 'userSession', $mockUserSession);
        $mockUserSession->expects($this->exactly(1))->method('getUser')->willReturn($mockUser);
    }

    /**
     * @param MockObject $mockUser
     */
    private function setMockUsersService(MockObject $mockUser)
    {
        $mockUsersService = $this->getMock('Craft\UsersService');
        $this->setComponent(craft(), 'users', $mockUsersService);
        $mockUsersService->expects($this->exactly(1))->method('getUserById')->willReturn($mockUser);
    }

    /**
     * @return MockObject|ElementCriteriaModel
     */
    private function getMockEntry()
    {
        $mockEntry = $this->getMockBuilder('Craft\EntryModel')
            ->disableOriginalConstructor()
            ->getMock();

        return $mockEntry;
    }

    /**
     * @return MockObject|ElementCriteriaModel
     */
    private function getMockCriteria()
    {
        $mockCriteria = $this->getMockBuilder('Craft\ElementCriteriaModel')
            ->disableOriginalConstructor()
            ->getMock();

        return $mockCriteria;
    }

    /**
     * @return MockObject
     */
    private function getMockUser()
    {
        $mockUser = $this->getMockBuilder('Craft\UserModel')
            ->disableOriginalConstructor()
            ->getMock();

        return $mockUser;
    }

    /**
     * @param string     $fieldHandle
     * @param MockObject $mockField
     */
    private function setMockFieldsService($fieldHandle, MockObject $mockField)
    {
        $mockFieldsService = $this->getMockBuilder('Craft\FieldsService')
            ->disableOriginalConstructor()
            ->getMock();
        $mockFieldsService->expects($this->exactly(1))->method('getFieldByHandle')
            ->with($fieldHandle)->willReturn($mockField);
        $this->setComponent(craft(), 'fields', $mockFieldsService);
    }

    /**
     * @param array      $settingsMap
     * @param MockObject $mockField
     */
    private function setMockSettings(array $settingsMap, MockObject $mockField)
    {
        $mockSettings = $this->getMockBuilder('Craft\BaseModel')
            ->disableOriginalConstructor()
            ->getMock();
        $mockSettings->expects($this->exactly(1))->method('getAttribute')
            ->willReturnCallback(function ($attribute) use ($settingsMap) {
                return @$settingsMap[$attribute];
            });

        $mockFieldType = $this->getMockBuilder('Craft\BaseElementFieldType')
            ->disableOriginalConstructor()
            ->getMock();
        $mockFieldType->expects($this->exactly(1))->method('getSettings')->willReturn($mockSettings);

        $mockField->expects($this->exactly(1))->method('getFieldType')->willReturn($mockFieldType);
    }

    /**
     * @return MockObject
     */
    protected function getMockTag()
    {
        $mockTag = $this->getMockBuilder('Craft\TagModel')
            ->disableOriginalConstructor()
            ->getMock();

        $mockContent = $this->getMockBuilder('Craft\Basemodel')
            ->disableOriginalConstructor()
            ->getMock();
        $mockTag->expects($this->any())->method('getContent')->willReturn($mockContent);

        return $mockTag;
    }

    /**
     * @param MockObject $mockTag
     */
    protected function setMockTagsService(MockObject $mockTag)
    {
        $tagsService = $this->getMockBuilder('Craft\TagsService')
            ->disableOriginalConstructor()
            ->getMock();
        $tagsService->expects($this->any())->method('saveTag')->with($mockTag)->willReturn(true);
        $this->setComponent(craft(), 'tags', $tagsService);
    }

    /**
     * Mock LocalizationService.
     */
    private function setMockLocalizationService()
    {
        $mock = $this->getMockBuilder('Craft\LocalizationService')
            ->disableOriginalConstructor()
            ->setMethods(array('getPrimarySiteLocaleId'))
            ->getMock();

        $mock->expects($this->any())->method('getPrimarySiteLocaleId')->willReturn('en_gb');

        $this->setComponent(craft(), 'i18n', $mock);
    }

    /**
     * @param array $criteria
     */
    protected function setMockElementsServiceForFieldType(array $criteria)
    {
        $mockCriteria = $this->getMockCriteria();
        foreach ($criteria['methods'] as $method => $return) {
            $mockCriteria->expects($this->atLeast(1))->method($method)->willReturn($return);
        }

        $mockElementsService = $this->getMock('Craft\ElementsService');
        $mockElementsService->expects($this->atLeast(1))->method('getCriteria')
            ->with($criteria['elementType'])->willReturn($mockCriteria);
        $this->setComponent(craft(), 'elements', $mockElementsService);
    }

    /**
     * Set mock categories service getGroupLocales.
     */
    protected function setMockCategoriesService()
    {
        $mockCategoriesService = $this->getMockBuilder('Craft\CategoriesService')
            ->disableOriginalConstructor()
            ->getMock();
        $mockCategoriesService->expects($this->any())->method('getGroupLocales')->willReturn(array());
        $this->setComponent(craft(), 'categories', $mockCategoriesService);
    }

    /**
     * Set mock assets service.
     *
     * @param string $file
     */
    protected function setMockAssetsService($file)
    {
        $mockAssetsService = $this->getMockBuilder('Craft\AssetsService')
            ->disableOriginalConstructor()
            ->getMock();

        $asset = $this->getMockAssetFileModel($file);

        $mockAssetsService->expects($this->any())->method('getFileById')->willReturn($asset);

        $this->setComponent(craft(), 'assets', $mockAssetsService);
    }

    /**
     * Get mock asset file model.
     *
     * @param string $file
     *
     * @return AssetFileModel
     */
    protected function getMockAssetFileModel($file)
    {
        $mockAssetFileModel = $this->getMockBuilder('Craft\AssetFileModel')
            ->disableOriginalConstructor()
            ->getMock();

        $source = $this->getMockAssetSourceModel($file);

        $mockAssetFileModel->expects($this->any())->method('getSource')->willReturn($source);

        return $mockAssetFileModel;
    }

    /**
     * Get mock asset source model.
     *
     * @param string $file
     *
     * @return AssetSourceModel
     */
    protected function getMockAssetSourceModel($file)
    {
        $mockAssetSourceModel = $this->getMockBuilder('Craft\AssetSourceModel')
            ->disableOriginalConstructor()
            ->getMock();

        $sourcetype = $this->getMockLocalAssetSourceType($file);

        $mockAssetSourceModel->expects($this->any())->method('getSourceType')->willReturn($sourcetype);

        return $mockAssetSourceModel;
    }

    /**
     * Mock LocalAssetSourceType.
     *
     * @param string $file
     *
     * @return AssetSourceModel
     */
    private function getMockLocalAssetSourceType($file)
    {
        $mock = $this->getMockBuilder('Craft\LocalAssetSourceType')
            ->disableOriginalConstructor()
            ->getMock();

        $mock->expects($this->any())->method('getLocalCopy')->willReturn($file);

        return $mock;
    }
}
