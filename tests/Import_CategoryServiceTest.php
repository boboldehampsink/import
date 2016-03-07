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
        require_once __DIR__.'/../services/IImportElementType.php';
        require_once __DIR__.'/../services/Import_CategoryService.php';
        require_once __DIR__.'/../models/Import_ElementModel.php';
        require_once __DIR__.'/../services/Import_HistoryService.php';
    }

    /**
     * Setup mock localization service.
     */
    public function setUp()
    {
        $this->setMockLocalizationService();
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
        $mockCategoryGroup = $this->getMockCategoryGroup();

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
            ),
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
            ),
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
        $mockCategoryModel = $this->getMockCategory();
        $category = $service->prepForElementModel($fields, $mockCategoryModel);

        $this->assertTrue($category instanceof CategoryModel);
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
        $mockCategoryModel = $this->getMockCategory();
        $category = $service->prepForElementModel($fields, $mockCategoryModel);

        $this->assertTrue($category instanceof CategoryModel);
        $this->assertCount(2, $fields);
    }

    /**
     * Save should call categories save.
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
     * Save should return false when saveCategory fails.
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
     * @param array $fields
     *
     * @covers ::callback
     * @dataProvider provideValidFieldsForCallback
     */
    public function testCallbackShouldSetParent(array $fields)
    {
        $category = new CategoryModel();
        $category->groupId = 1;
        $mockParentCategory = $this->getMockCategory();
        $mockCategoryGroup = $this->getMockCategoryGroup();

        if (isset($fields[Import_ElementModel::HandleParent]) || isset($fields[Import_ElementModel::HandleAncestors])) {
            $mockCriteria = $this->getMockCriteria();
            $mockCriteria->expects($this->exactly(1))->method('first')->willReturn($mockParentCategory);

            $this->setMockElementsService($mockCriteria);

            $mockCategoriesService = $this->getMock('Craft\CategoriesService');
            $mockCategoriesService->expects($this->any())->method('getGroupById')
                ->with(1)->willReturn($mockCategoryGroup);
            $this->setComponent(craft(), 'categories', $mockCategoriesService);

            $mockStructuresService = $this->getMock('Craft\StructuresService');
            $mockStructuresService->expects($this->exactly(1))->method('append')
                ->with(null, $category, $mockParentCategory, 'auto');
            $this->setComponent(craft(), 'structures', $mockStructuresService);
        }

        if (isset($fields[Import_ElementModel::HandleAncestors])) {
            $mockImportService = $this->getMock('Craft\ImportService');
            $mockImportService->expects($this->exactly(1))->method('slugify')
                ->with($fields['ancestors'])->willReturn('slugified-slug');
            $this->setComponent(craft(), 'import', $mockImportService);
        }

        $service = new Import_CategoryService();
        $service->callback($fields, $category);
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
            'locale' => 'en_gb',
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
                    'locale' => 'nl_nl',
                    'slug' => 'test-slug',
                )),
            ),
        );
    }

    /**
     * @return array
     */
    public function provideValidFieldsForCallback()
    {
        return array(
            'Parent given' => array(
                'fields' => array(
                    'parentId' => 'news',
                ),
            ),
            'Ancestors given' => array(
                'fields' => array(
                    'ancestors' => 'news and stuff',
                ),
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
        $mockContent = $this->getMockBuilder('Craft\BaseModel')
            ->disableOriginalConstructor()
            ->getMock();
        $mockCategory->expects($this->any())->method('getContent')->willReturn($mockContent);

        return $mockCategory;
    }

    /**
     * @param MockObject $mockCategory
     * @param bool       $success
     */
    private function setMockCategoriesServiceSave(MockObject $mockCategory, $success)
    {
        $categoriesService = $this->getMock('Craft\CategoriesService');
        $categoriesService->expects($this->exactly(1))->method('saveCategory')->with($mockCategory)->willReturn($success);
        $this->setComponent(craft(), 'categories', $categoriesService);
    }

    /**
     * @return MockObject
     */
    private function getMockCategoryGroup()
    {
        $mockCategoryGroup = $this->getMockBuilder('Craft\CategoryGroupModel')
            ->disableOriginalConstructor()
            ->getMock();

        return $mockCategoryGroup;
    }

    /**
     * Mock LocalizationService.
     */
    private function setMockLocalizationService()
    {
        $mock = $this->getMockBuilder('Craft\LocalizationService')
            ->disableOriginalConstructor()
            ->setMethods(array('getPrimarySiteLocaleId'))
            ->getMock();

        $mock->expects($this->any())->method('getPrimarySiteLocaleId')->willReturn('en_gb');

        $this->setComponent(craft(), 'i18n', $mock);
    }
}
