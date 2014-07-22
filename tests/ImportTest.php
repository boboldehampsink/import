<?php
namespace Craft;

class ImportTest extends \UnitTestCase 
{
    
    function testHistoryShowLog() 
    {
        $log = craft()->import_history->showLog(1);
        $this->assertTrue(is_array($log));
        $this->assertTrue($log > 0);
    }
    
    function testHistoryLog() 
    {
        $log = craft()->import_history->log($historyId = 1, $line = 0, $errors = array());
        $this->assertSame($log, $errors);
    }
    
    function testPrepForEntryModel()
    {
        $fields = array('title' => 'test');
        $entry = craft()->import->prepForEntryModel($fields, new EntryModel());
        $this->assertTrue($entry instanceOf EntryModel);
    }
    
    function testPrepForFieldType()
    {
        $data = ' u0';
        craft()->import->prepForFieldType($data, 'price');
        $this->assertTrue(is_numeric($data));
    }
    
}