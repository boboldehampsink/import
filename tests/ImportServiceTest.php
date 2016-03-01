<?php

namespace Craft;

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
        require_once __DIR__.'/../services/ImportService.php';
        require_once __DIR__.'/../models/ImportModel.php';
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
}
