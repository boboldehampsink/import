<?php

namespace Craft;

use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Contains unit tests for the Import_CategoryService.
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 *
 * @coversDefaultClass Craft\Import_CategoryService
 * @covers ::<!public>
 */
class Import_CategoryServiceTest extends BaseTest
{
    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        // Set up parent
        parent::setUpBeforeClass();

        // Require dependencies
        require_once __DIR__ . '/../services/IImportElementType.php';
        require_once __DIR__ . '/../services/Import_CategoryService.php';
        require_once __DIR__ . '/../models/Import_ElementModel.php';
        require_once __DIR__ . '/../services/Import_HistoryService.php';
    }

    /**
     * Import_CategoryService should implement IImportElementType.
     */
    public function testImportCategoryServiceShouldImplementIExportElementType()
    {
        $this->assertInstanceOf('Craft\IImportElementType', new Import_CategoryService());
    }

    /**
     * @covers ::getTemplate
     */
    public function testGetTemplateShouldReturnCategoryUploadTemplate()
    {
        $template = 'import/types/category/_upload';

        $service = new Import_CategoryService();
        $result = $service->getTemplate();

        $this->assertSame($template, $result);
    }

    /**
     * @covers ::getGroups
     */
    public function testGetGroupsShouldReturnEditableCategoryGroups()
    {
        $mockCategoryGroup = $this->getMockBuilder('Craft\CategoryGroupModel')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEditableCategoryGroups = array($mockCategoryGroup);

        $this->setMockCategoriesService($mockEditableCategoryGroups);

        $service = new Import_CategoryService();
        $result = $service->getGroups();

        $this->assertSame(array($mockCategoryGroup), $result);
    }

    /**
     * @covers ::setModel
     */
    public function testSetModelShouldSetElementVars()
    {
        $groupId = 1;

        $settings = array(
            'elementvars' => array(
                'group' => $groupId,
            )
        );

        $service = new Import_CategoryService();
        $result = $service->setModel($settings);

        $this->assertInstanceOf('Craft\CategoryModel', $result);
        $this->assertSame($groupId, $result->groupId);
    }

    /**
     * @covers ::setCriteria
     */
    public function testSetCriteriaShouldSetElementVars()
    {
        $groupId = 1;

        $settings = array(
            'elementvars' => array(
                'group' => $groupId,
            )
        );

        $mockCriteria = $this->getMockCriteria();
        $mockCriteria->expects($this->exactly(3))->method('__set')
            ->withConsecutive(
                array('limit', null),
                array('status', null),
                array('groupId', $groupId)
            );
        $this->setMockElementsService($mockCriteria);

        $service = new Import_CategoryService();
        $result = $service->setCriteria($settings);

        $this->assertInstanceOf('Craft\ElementCriteriaModel', $result);
    }

    /**
     * @covers ::delete
     */
    public function testDeleteShouldCallCategoriesDelete()
    {
        $mockCategory = $this->getMockCategory();
        $elements = array($mockCategory);

        $categoriesService = $this->getMock('Craft\CategoriesService');
        $categoriesService->expects($this->exactly(1))->method('deleteCategory')->with($elements);
        $this->setComponent(craft(), 'categories', $categoriesService);

        $service = new Import_CategoryService();
        $service->delete($elements);
    }

    /**
     * Test preparing value for elementmodel.
     *
     * @param array $fields
     * @param array $expectedAttributes
     *
     * @covers ::prepForElementModel
     * @dataProvider provideValidFieldsForElement
     */
    public function testPrepForElementModelShouldHandleSpecifiedAttributes(array $fields, array $expectedAttributes)
    {
        $title = @$fields['title'];

        $service = new Import_CategoryService();
        $category = $service->prepForElementModel($fields, new CategoryModel());

        $this->assertTrue($category instanceof CategoryModel);
        $this->assertEquals($expectedAttributes, $category->getAttributes());
        $this->assertSame($title, $category->title);
        $this->assertCount(0, $fields);
    }

    /**
     * @covers ::prepForElementModel
     */
    public function testPrepForElementModelShouldIgnoreUnspecifiedAttributes()
    {
        $fields = array(
            'test' => 'value',
            'test2' => 'value2',
        );

        $service = new Import_CategoryService();
        $category = $service->prepForElementModel($fields, new CategoryModel());

        $this->assertTrue($category instanceof CategoryModel);
        $this->assertCount(2, $fields);
    }

    /**
     * Save should call categories save
     *
     * @covers ::save
     */
    public function testSaveShouldCallCategoriesSave()
    {
        $settings = array();

        $mockCategory = $this->getMockCategory();

        $this->setMockCategoriesServiceSave($mockCategory, true);

        $service = new Import_CategoryService();
        $result = $service->save($mockCategory, $settings);

        $this->assertTrue($result);
    }

    /**
     * Save should return false when saveCategory fails
     *
     * @covers ::save
     */
    public function testSaveShouldReturnFalseWhenSaveFails()
    {
        $settings = array();
        $mockCategory = $this->getMockCategory();

        $this->setMockCategoriesServiceSave($mockCategory, false);

        $service = new Import_CategoryService();
        $result = $service->save($mockCategory, $settings);

        $this->assertFalse($result);
    }

    /**
     * @covers ::callback
     */
    public function testCallbackShouldDoNothing()
    {
        $fields = array();
        $mockCategory = $this->getMockCategory();

        $service = new Import_CategoryService();
        $service->callback($fields, $mockCategory);
    }

    /**
     * @return array
     */
    public function provideValidFieldsForElement()
    {
        $defaultExpectedAttributes = array(
            'id' => null,
            'enabled' => true,
            'archived' => false,
            'locale' => "en_gb",
            'localeEnabled' => true,
            'slug' => null,
            'uri' => null,
            'dateCreated' => null,
            'dateUpdated' => null,
            'root' => null,
            'lft' => null,
            'rgt' => null,
            'level' => null,
            'searchScore' => null,
            'groupId' => null,
            'newParentId' => null,
        );

        return array(
            'Basic attributes' => array(
                'fields' => array(
                    'title' => 'test',
                    'id' => 1,
                    'locale' => 'nl_nl',
                    'slug' => 'Test slug',
                ),
                'expectedAttributes' => array_merge($defaultExpectedAttributes, array(
                    'id' => 1,
                    'locale' => "nl_nl",
                    'slug' => 'test-slug',
                )),
            ),
        );
    }

    /**
     * @param MockObject[] $mockEditableCategoryGroups
     */
    private function setMockCategoriesService(array $mockEditableCategoryGroups)
    {
        $mockCategoriesService = $this->getMock('Craft\CategoriesService');
        $mockCategoriesService->expects($this->exactly(1))
            ->method('getEditableGroups')->willReturn($mockEditableCategoryGroups);
        $this->setComponent(craft(), 'categories', $mockCategoriesService);
    }

    /**
     * @return MockObject|ElementCriteriaModel
     */
    private function getMockCriteria()
    {
        $mockCriteria = $this->getMockBuilder('Craft\ElementCriteriaModel')
            ->disableOriginalConstructor()
            ->getMock();
        return $mockCriteria;
    }

    /**
     * @param MockObject $mockCriteria
     */
    private function setMockElementsService(MockObject $mockCriteria)
    {
        $mockElementsService = $this->getMock('Craft\ElementsService');
        $mockElementsService->expects($this->exactly(1))->method('getCriteria')->willReturn($mockCriteria);
        $this->setComponent(craft(), 'elements', $mockElementsService);
    }

    /**
     * @return MockObject|CategoryModel
     */
    private function getMockCategory()
    {
        $mockCategory = $this->getMockBuilder('Craft\CategoryModel')
            ->disableOriginalConstructor()
            ->getMock();
        return $mockCategory;
    }

    /**
     * @param MockObject $mockCategory
     * @param bool $success
     */
    private function setMockCategoriesServiceSave(MockObject $mockCategory, $success)
    {
        $categoriesService = $this->getMock('Craft\CategoriesService');
        $categoriesService->expects($this->exactly(1))->method('saveCategory')->with($mockCategory)->willReturn($success);
        $this->setComponent(craft(), 'categories', $categoriesService);
    }
}
