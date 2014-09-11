<?php
namespace Craft;

class ImportTest extends BaseTest 
{
    
    protected $importHistoryService;
    protected $importService;
    
    public function setUp()
    {
    
        // Get dependencies
        $dir = __DIR__;
        $map = array(
            '\\Craft\\ImportModel'           => '/../models/ImportModel.php',
            '\\Craft\\Import_EntriesRecord'  => '/../records/Import_EntriesRecord.php',
            '\\Craft\\Import_HistoryRecord'  => '/../records/Import_HistoryRecord.php',
            '\\Craft\\Import_LogRecord'      => '/../records/Import_LogRecord.php',
            '\\Craft\\Import_HistoryService' => '/../services/Import_HistoryService.php',
            '\\Craft\\ImportService'         => '/../services/ImportService.php',
            '\\Craft\\Import_EntryService'   => '/../services/Import_EntryService.php'
        );

        // Inject them
        foreach($map as $classPath => $filePath) {
            if(!class_exists($classPath, false)) {
                require_once($dir . $filePath);
            }
        }
    
        // Construct them
        $this->importHistoryService = new Import_HistoryService;
        $this->importService        = new ImportService;
        $this->importEntryService   = new Import_EntryService;
    
    } 
    
    public function testHistoryShowLog() 
    {
    
        $log = $this->importHistoryService->showLog(1);
        
        $this->assertTrue(is_array($log));
        $this->assertTrue($log > 0);
        
    }
    
    public function testHistoryLog() 
    {
    
        $log = $this->importHistoryService->log($historyId = 1, $line = 0, $errors = array());
        
        $this->assertSame($log, $errors);
        
    }
    
    public function testPrepForElementModel()
    {
    
        $fields = array('title' => 'test');
        $entry = $this->importEntryService->prepForElementModel($fields, new EntryModel());
        
        $this->assertTrue($entry instanceOf EntryModel);
        
    }
    
    public function testPrepForFieldType()
    {
    
        $data = ' u0';
        $this->importService->prepForFieldType($data, 'price');
        
        $this->assertTrue(is_numeric($data));
        
    }
    
}