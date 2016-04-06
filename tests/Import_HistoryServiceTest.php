<?php

namespace Craft;

use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Contains unit tests for the Import_HistoryService.
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 *
 * @coversDefaultClass Craft\Import_HistoryService
 * @covers ::<!public>
 */
class Import_HistoryServiceTest extends BaseTest
{
    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        // Set up parent
        parent::setUpBeforeClass();

        // Require dependencies
        require_once __DIR__.'/../services/Import_HistoryService.php';
        require_once __DIR__.'/../records/Import_LogRecord.php';
        require_once __DIR__.'/../records/Import_HistoryRecord.php';
        require_once __DIR__.'/../records/Import_EntriesRecord.php';
    }

    /**
     * Setup mock localization service.
     */
    public function setUp()
    {
        $this->setMockLocalizationService();
    }

    /**
     * @covers ::show
     */
    public function testFindAllHistoriesShouldGetAllHistories()
    {
        $mockResult = array('histories');

        $service = $this->getImportHistoryService(array('findAllHistories'));
        $service->expects($this->exactly(1))->method('findAllHistories')
            ->with($this->isInstanceOf('CDbCriteria'))->willReturn($mockResult);

        $result = $service->show();

        $this->assertSame($mockResult, $result);
    }

    /**
     * Test history log detail.
     *
     * @covers ::showLog
     */
    public function testShowLogShouldReturnArray()
    {
        $historyId = 1;
        $mockHistory = $this->getMockHistory(array(
            array('rows', 3),
        ));
        $mockResult = array(
            'histories' => array(
                'line' => 2,
                'errors' => array(),
            ),
        );

        $service = $this->getImportHistoryService(array('findAllLogs', 'findHistoryById'));
        $service->expects($this->exactly(1))->method('findAllLogs')
            ->with($this->isInstanceOf('CDbCriteria'))->willReturn($mockResult);
        $service->expects($this->exactly(1))->method('findHistoryById')->with($historyId)->willReturn($mockHistory);

        $log = $service->showLog(1);

        $this->assertTrue(is_array($log));
        $this->assertTrue($log > 0);
    }

    /**
     * Test history logging.
     *
     * @covers ::log
     */
    public function testLogShouldReturnErrors()
    {
        $historyId = 1;
        $mockImportLogRecord = $this->getMockBuilder('Craft\Import_LogRecord')
            ->disableOriginalConstructor()
            ->getMock();
        $service = $this->getImportHistoryService(array('findHistoryById', 'getNewImportLogRecord'));
        $service->expects($this->exactly(1))->method('findHistoryById')->with($historyId)->willReturn(true);
        $service->expects($this->exactly(1))->method('getNewImportLogRecord')->willReturn($mockImportLogRecord);

        $log = $service->log($historyId, 0, $errors = array());

        $this->assertSame($log, $errors);
    }

    /**
     * @covers ::start
     */
    public function testStartShouldSaveNewHistoryRecord()
    {
        $historyId = 1;
        $settings = array(
            'user' => 1,
            'type' => 'type',
            'file' => 'file',
            'rows' => 3,
            'behavior' => 'behavior',
        );
        $mockHistory = $this->getMockHistory(array(
            array('id', $historyId),
        ));
        $mockHistory->expects($this->exactly(1))->method('save')->with(false);

        $file = __DIR__.'/tst_csv.csv';
        $this->setMockAssetsService($file);

        $service = $this->getImportHistoryService(array('getNewImportHistoryRecord'));
        $service->expects($this->exactly(1))->method('getNewImportHistoryRecord')->willReturn($mockHistory);

        $result = $service->start($settings);
        $this->assertSame($historyId, $result);
    }

    /**
     * @covers ::end
     */
    public function testEndShouldSaveHistory()
    {
        $historyId = 1;
        $status = 'active';

        $mockHistory = $this->getMockHistory();
        $mockHistory->expects($this->exactly(1))->method('__set')->with('status', $status);
        $mockHistory->expects($this->exactly(1))->method('save')->with(false);

        $service = $this->getImportHistoryService(array('findHistoryById'));
        $service->expects($this->exactly(1))->method('findHistoryById')->with($historyId)->willReturn($mockHistory);

        $service->end($historyId, $status);
    }

    /**
     * @covers ::clear
     */
    public function testClearShouldDoNothing()
    {
        $service = new Import_HistoryService();
        $result = $service->clear(1);
        $this->assertNull($result);
    }

    /**
     * @covers ::version
     */
    public function testVersionShouldSaveNewImportEntriesRecord()
    {
        $historyId = 1;
        $entryId = 2;

        $mockEntryRevisionsService = $this->getMock('Craft\EntryRevisionsService');
        $mockEntryRevisionsService->expects($this->exactly(1))->method('getVersionsByEntryId')
            ->with($entryId, false, 2)->willReturn(array());
        $this->setComponent(craft(), 'entryRevisions', $mockEntryRevisionsService);

        $mockImportEntriesRecord = $this->getMockImportEntriesRecord();

        $service = $this->getImportHistoryService(array('getNewImportEntriesRecord'));
        $service->expects($this->exactly(1))->method('getNewImportEntriesRecord')->willReturn($mockImportEntriesRecord);

        $service->version($historyId, $entryId);
    }

    /**
     * @param array $attributesMap
     *
     * @return MockObject
     */
    private function getMockHistory(array $attributesMap = array())
    {
        $mockHistory = $this->getMockBuilder('Craft\Import_HistoryRecord')
            ->disableOriginalConstructor()
            ->getMock();
        $mockHistory->expects($this->any())->method('__get')->willReturnMap($attributesMap);

        return $mockHistory;
    }

    /**
     * @param array $mockedMethods
     *
     * @return MockObject|Import_historyService $service
     */
    private function getImportHistoryService(array $mockedMethods)
    {
        $service = $this->getMock('Craft\Import_HistoryService', $mockedMethods);

        return $service;
    }

    /**
     * @return MockObject
     */
    private function getMockImportEntriesRecord()
    {
        $mockImportEntriesRecord = $this->getMockBuilder('Craft\Import_EntriesRecord')
            ->disableOriginalConstructor()
            ->getMock();

        return $mockImportEntriesRecord;
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
