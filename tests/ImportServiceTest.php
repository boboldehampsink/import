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
        $this->setMockImportHistoryService($historyId, $row, $this->isType('array'));

        $service = new ImportService();
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
     * @return MockObject
     */
    private function setMockImportEntryService(array $settings, MockObject $mockEntry, array $fields, $saveSuccess)
    {
        $mockImportTypeService = $this->getMock('Craft\Import_EntryService');
        $mockImportTypeService->expects($this->exactly(1))->method('setModel')->with($settings)->willReturn($mockEntry);
        $mockImportTypeService->expects($this->exactly(1))->method('prepForElementModel')
            ->with($fields, $mockEntry)->willReturn($mockEntry);
        $mockImportTypeService->expects($this->exactly(1))->method('save')
            ->with($mockEntry, $settings)->willReturn($saveSuccess);
        $this->setComponent(craft(), 'import_typeexists', $mockImportTypeService);
        return $mockImportTypeService;
    }

    /**
     * @return MockObject
     */
    private function getMockEntry()
    {
        $mockEntry = $this->getMockBuilder('Craft\EntryModel')
            ->disableOriginalConstructor()
            ->getMock();
        return $mockEntry;
    }
}
