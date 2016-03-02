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
        require_once __DIR__ . '/../services/Import_HistoryService.php';
        require_once __DIR__ . '/../records/Import_LogRecord.php';
        require_once __DIR__ . '/../records/Import_HistoryRecord.php';
        require_once __DIR__ . '/../records/Import_EntriesRecord.php';
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
            array('rows', 3)
        ));
        $mockResult = array(
            'histories' => array(
                'line' => 2,
                'errors' => array(),
            )
        );

        $service = $this->getImportHistoryService(array('findAllHistories', 'findHistoryById'));
        $service->expects($this->exactly(1))->method('findAllHistories')
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
        $service = new Import_HistoryService();
        $log = $service->log($historyId = 1, $line = 0, $errors = array());

        $this->assertSame($log, $errors);
    }

    /**
     * @covers ::start
     */
    public function testStartShouldSaveNewHistoryRecord()
    {
        $historyId = 1;
        $settings = array(
            'type' => 'type',
            'file' => 'file',
            'rows' => 3,
            'behavior' => 'behavior',
        );
        $mockHistory = $this->getMockHistory(array(
            array('id', $historyId)
        ));
        $mockHistory->expects($this->exactly(1))->method('save')->with(false);

        $mockUser = $this->getMockUser();
        $this->setMockUserSession($mockUser);

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
     * @param $mockUser
     */
    private function setMockUserSession($mockUser)
    {
        $mockUserSession = $this->getMock('Craft\UserSessionService');
        $mockUserSession->expects($this->exactly(1))->method('getUser')->willReturn($mockUser);
        $this->setComponent(craft(), 'userSession', $mockUserSession);
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
     * @param array $mockedMethods
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
}
