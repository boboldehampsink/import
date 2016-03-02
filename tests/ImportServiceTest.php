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
        require_once __DIR__ . '/../services/ImportService.php';
        require_once __DIR__ . '/../services/Import_HistoryService.php';
        require_once __DIR__ . '/../services/IImportElementType.php';
        require_once __DIR__ . '/../services/Import_EntryService.php';
        require_once __DIR__ . '/../models/ImportModel.php';
    }

    /**
     * @covers ::columns
     */
    public function testColumnsShouldReturnColumnRow()
    {
        $file = __DIR__ . '/tst_csv.csv';
        $expectedColumns = array('column1', 'column2', 'column3', 'column4', 'column5');

        $service = new ImportService();
        $result = $service->columns($file);

        $this->assertSame($expectedColumns, $result);
    }


    /**
     * @covers ::data
     */
    public function testDataShouldReturnDataRows()
    {
        $file = __DIR__ . '/tst_csv.csv';
        $expectedData = array(
            array('row1value1', 'row1value2', 'row1value3', 'row1value4', 'row1value5'),
            array('row1value1', 'row2value2', 'row3value3', 'row4value4', 'row5value5'),
        );

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
            function($method) use ($mockException) {
                if($method == 'registerImportService'){
                    return null;
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
        $this->setMockUserSession($mockUser);

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
        $this->setMockUserSession($mockUser);

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
        $this->setMockUserSession($mockUser);

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
            'map' => array('field1' => 'field1', 'field2' => 'field2'),
            'type' => 'TypeExists',
            'unique' => array('field1' => 1, 'field2' => 0),
            'history' => $historyId,
            'behavior' => ImportModel::BehaviorDelete,
        );

        $data = array('settings1' => 'value1', 'settings2' => 'value2');
        $fields = array_combine($settings['map'], $data);

        $mockEntry = $this->getMockEntry();
        $mockException = $this->getMock('Craft\Exception');;
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
        $this->setMockUserSession($mockUser);

        /** @var ImportService $service */
        $service = $this->getMock('Craft\ImportService', array('onBeforeImportDelete'));
        $service->row($row, $data, $settings);
    }

    /**
     * Test preparing value for field type.
     *
     * @covers ::prepForFieldType
     */
    public function testPrepForFieldType()
    {
        $data = ' u0';

        $service = new ImportService();
        $service->prepForFieldType($data, 'price');

        $this->assertTrue(is_numeric($data));
    }

    /**
     * @param int $historyId
     * @param int $row
     * @param string $message
     */
    private function setMockImportHistoryService($historyId, $row, $message)
    {
        $mockImportHistoryService = $this->getMock('Craft\Import_HistoryService');
        $mockImportHistoryService->expects($this->any())->method('log')->with($historyId, $row, $message);
        $this->setComponent(craft(), 'import_history', $mockImportHistoryService);
    }

    /**
     * @param array $settings
     * @param MockObject $mockEntry
     * @param array $fields
     * @param bool $saveSuccess
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
}
