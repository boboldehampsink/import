<?php

namespace Craft;

/**
 * Import Test.
 *
 * Unit tests for Import Plugin
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 */
class ImportTest extends BaseTest
{
    /**
     * Load plugin component.
     */
    public function setUp()
    {
        // Load plugins
        $pluginsService = craft()->getComponent('plugins');
        $pluginsService->loadPlugins();
    }

    /**
     * Test history log detail.
     */
    public function testHistoryShowLog()
    {
        $log = craft()->import_history->showLog(1);

        $this->assertTrue(is_array($log));
        $this->assertTrue($log > 0);
    }

    /**
     * Test history logging.
     */
    public function testHistoryLog()
    {
        $log = craft()->import_history->log($historyId = 1, $line = 0, $errors = array());

        $this->assertSame($log, $errors);
    }

    /**
     * Test preparing value for elementmodel.
     */
    public function testPrepForElementModel()
    {
        $fields = array('title' => 'test');
        $entry = craft()->import_entry->prepForElementModel($fields, new EntryModel());

        $this->assertTrue($entry instanceof EntryModel);
    }

    /**
     * Test preparing value for field type.
     */
    public function testPrepForFieldType()
    {
        $data = ' u0';
        craft()->import->prepForFieldType($data, 'price');

        $this->assertTrue(is_numeric($data));
    }
}
