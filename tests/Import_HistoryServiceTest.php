<?php

namespace Craft;

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
    }

    /**
     * Test history log detail.
     *
     * @covers ::showLog
     */
    public function testHistoryShowLog()
    {
        $service = new Import_HistoryService();
        $log = $service->showLog(1);

        $this->assertTrue(is_array($log));
        $this->assertTrue($log > 0);
    }

    /**
     * Test history logging.
     *
     * @covers ::log
     */
    public function testHistoryLog()
    {
        $service = new Import_HistoryService();
        $log = $service->log($historyId = 1, $line = 0, $errors = array());

        $this->assertSame($log, $errors);
    }
}
