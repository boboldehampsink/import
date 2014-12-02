<?php
namespace Craft;

class ImportTest extends BaseTest 
{
    
    public function setUp()
    {
    
        // PHPUnit complains about not settings this
        date_default_timezone_set('UTC');
    
        // Get dependencies
        $dir = __DIR__;
        $map = array(
            '\\Craft\\ImportModel'           => '/../models/ImportModel.php',
            '\\Craft\\Import_EntriesRecord'  => '/../records/Import_EntriesRecord.php',
            '\\Craft\\Import_HistoryRecord'  => '/../records/Import_HistoryRecord.php',
            '\\Craft\\Import_LogRecord'      => '/../records/Import_LogRecord.php',
            '\\Craft\\Import_HistoryService' => '/../services/Import_HistoryService.php',
            '\\Craft\\ImportService'         => '/../services/ImportService.php',
            '\\Craft\\Import_EntryService'   => '/../services/Import_EntryService.php',
            '\\Craft\\Import_ElementModel'     => '/../models/Import_ElementModel.php'
        );

        // Inject them
        foreach($map as $classPath => $filePath) {
            if(!class_exists($classPath, false)) {
                require_once($dir . $filePath);
            }
        }
    
        // Construct them
        $this->setComponent(craft(), 'import_history', new Import_HistoryService);
        $this->setComponent(craft(), 'import', new ImportService);
        $this->setComponent(craft(), 'import_entry', new Import_EntryService);
    
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
        
        $this->assertTrue($entry instanceOf EntryModel);
        
    }
    
    public function testPrepForFieldType()
    {
    
        $data = ' u0';
        craft()->import->prepForFieldType($data, 'price');
        
        $this->assertTrue(is_numeric($data));
        
    }
    
}