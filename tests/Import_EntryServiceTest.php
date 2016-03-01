<?php

namespace Craft;

/**
 * Contains unit tests for the Import_EntryService.
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 *
 * @coversDefaultClass Craft\Import_EntryService
 * @covers ::<!public>
 */
class Import_EntryServiceTest extends BaseTest
{
    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        // Set up parent
        parent::setUpBeforeClass();

        // Require dependencies
        require_once __DIR__.'/../services/IImportElementType.php';
        require_once __DIR__.'/../services/Import_EntryService.php';
        require_once __DIR__.'/../models/Import_ElementModel.php';
    }

    /**
     * Test preparing value for elementmodel.
     *
     * @covers ::prepForElementModel
     */
    public function testPrepForElementModel()
    {
        $fields = array('title' => 'test');

        $service = new Import_EntryService();
        $entry = $service->prepForElementModel($fields, new EntryModel());

        $this->assertTrue($entry instanceof EntryModel);
    }
}
