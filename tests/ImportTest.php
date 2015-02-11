<?php
namespace Craft;

class ImportTest extends BaseTest
{

    public function setUp()
    {

        // Load plugins
        $pluginsService = craft()->getComponent('plugins');
        $pluginsService->loadPlugins();
    }

    public function testHistoryShowLog()
    {
        $log = craft()->import_history->showLog(1);

        $this->assertTrue(is_array($log));
        $this->assertTrue($log > 0);
    }

    public function testHistoryLog()
    {
        $log = craft()->import_history->log($historyId = 1, $line = 0, $errors = array());

        $this->assertSame($log, $errors);
    }

    public function testPrepForElementModel()
    {
        $fields = array('title' => 'test');
        $entry = craft()->import_entry->prepForElementModel($fields, new EntryModel());

        $this->assertTrue($entry instanceof EntryModel);
    }

    public function testPrepForFieldType()
    {
        $data = ' u0';
        craft()->import->prepForFieldType($data, 'price');

        $this->assertTrue(is_numeric($data));
    }
}
