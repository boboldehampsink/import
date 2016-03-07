<?php

namespace Craft;

use PHPUnit_Framework_MockObject_MockObject as MockObject;

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
    }

    /**
     * Setup mock localization service.
     */
    public function setUp()
    {
        $this->setMockLocalizationService();
    }

    /**
     * Import_EntryService should implement IImportElementType.
     */
    public function testImportEntryServiceShouldImplementIExportElementType()
    {
        $this->assertInstanceOf('Craft\IImportElementType', new Import_EntryService());
    }

    /**
     * @covers ::getTemplate
     */
    public function testGetTemplateShouldReturnEntryUploadTemplate()
    {
        $template = 'import/types/entry/_upload';

        $service = new Import_EntryService();
        $result = $service->getTemplate();

        $this->assertSame($template, $result);
    }

    /**
     * @covers ::getGroups
     */
    public function testGetGroupsShouldReturnEditableSectionsExceptSingles()
    {
        $mockSection = $this->getMockSection(SectionType::Channel);
        $mockSingle = $this->getMockSection(SectionType::Single);

        $mockEditableSections = array($mockSection, $mockSingle);

        $this->setMockSectionsService($mockEditableSections);

        $service = new Import_EntryService();
        $result = $service->getGroups();

        $this->assertSame(array($mockSection), $result);
    }

    /**
     * @covers ::setModel
     */
    public function testSetModelShouldSetElementVars()
    {
        $sectionId = 1;
        $entryTypeId = 2;

        $settings = array(
            'elementvars' => array(
                'section' => $sectionId,
                'entrytype' => $entryTypeId,
            ),
        );

        $service = new Import_EntryService();
        $result = $service->setModel($settings);

        $this->assertInstanceOf('Craft\EntryModel', $result);
        $this->assertSame($sectionId, $result->sectionId);
        $this->assertSame($entryTypeId, $result->typeId);
    }

    /**
     * @covers ::setCriteria
     */
    public function testSetCriteriaShouldSetElementVars()
    {
        $sectionId = 1;
        $entryTypeId = 2;

        $settings = array(
            'elementvars' => array(
                'section' => $sectionId,
                'entrytype' => $entryTypeId,
            ),
        );

        $mockCriteria = $this->getMockCriteria();
        $mockCriteria->expects($this->exactly(4))->method('__set')
            ->withConsecutive(
                array('limit', null),
                array('status', null),
                array('sectionId', $sectionId),
                array('type', $entryTypeId)
            );
        $this->setMockElementsService($mockCriteria);

        $service = new Import_EntryService();
        $result = $service->setCriteria($settings);

        $this->assertInstanceOf('Craft\ElementCriteriaModel', $result);
    }

    /**
     * @covers ::delete
     */
    public function testDeleteShouldCallEntriesDelete()
    {
        $mockEntry = $this->getMockEntry();
        $elements = array($mockEntry);

        $entriesService = $this->getMock('Craft\EntriesService');
        $entriesService->expects($this->exactly(1))->method('deleteEntry')->with($elements);
        $this->setComponent(craft(), 'entries', $entriesService);

        $service = new Import_EntryService();
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

        if (!isset($fields[Import_ElementModel::HandleAuthor])) {
            $this->setMockUserSession();
        } elseif (!is_numeric($fields[Import_ElementModel::HandleAuthor])) {
            $this->setMockUsersService($fields['authorId']);
        }

        if (isset($fields[Import_ElementModel::HandleParent]) || isset($fields[Import_ElementModel::HandleAncestors])) {
            $mockCriteria = $this->getMockCriteria();
            $mockCriteria->expects($this->exactly(1))->method('total')->willReturn(1);
            $mockCriteria->expects($this->exactly(1))->method('ids')->willReturn(array(
                $expectedAttributes['parentId'], 1, 2, 3, 4,
            ));

            $this->setMockElementsService($mockCriteria);
        }

        if (isset($fields[Import_ElementModel::HandleAncestors])) {
            $mockImportService = $this->getMock('Craft\ImportService');
            $mockImportService->expects($this->exactly(1))->method('slugify')
                ->with($fields['ancestors'])->willReturn('slugified-slug');
            $this->setComponent(craft(), 'import', $mockImportService);
        }

        $service = new Import_EntryService();
        $mockEntry = $this->getMockEntry();
        $entry = $service->prepForElementModel($fields, $mockEntry);

        $this->assertTrue($entry instanceof EntryModel);
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

        $service = new Import_EntryService();
        $entry = $service->prepForElementModel($fields, new EntryModel());

        $this->assertTrue($entry instanceof EntryModel);
        $this->assertCount(2, $fields);
    }

    /**
     * Save should call entries save.
     *
     * @covers ::save
     */
    public function testSaveShouldCallEntriesSave()
    {
        $entryId = 1;
        $settings = array(
            'history' => 'historySettings',
        );

        $mockEntry = $this->getMockEntry();
        $mockEntry->expects($this->exactly(1))->method('__get')->with('id')->willReturn($entryId);

        $this->setMockEntriesServiceSave($mockEntry, true);

        $mockImportHistoryService = $this->getMock('Craft\Import_HistoryService');
        $mockImportHistoryService->expects($this->exactly(1))->method('version')->with($settings['history'], $entryId);
        $this->setComponent(craft(), 'import_history', $mockImportHistoryService);

        $service = $this->getMockBuilder('Craft\Import_EntryService')
            ->setMethods(array('getCraftEdition'))
            ->getMock();
        $service->expects($this->exactly(1))->method('getCraftEdition')->willReturn(Craft::Pro);

        $result = $service->save($mockEntry, $settings);

        $this->assertTrue($result);
    }

    /**
     * Save should return false when saveEntry fails.
     *
     * @covers ::save
     */
    public function testSaveShouldReturnFalseWhenSaveFails()
    {
        $settings = array();
        $mockEntry = $this->getMockEntry();

        $this->setMockEntriesServiceSave($mockEntry, false);

        $service = new Import_EntryService();
        $result = $service->save($mockEntry, $settings);

        $this->assertFalse($result);
    }

    /**
     * @covers ::callback
     */
    public function testCallbackShouldDoNothing()
    {
        $fields = array();
        $mockEntry = $this->getMockEntry();

        $service = new Import_EntryService();
        $service->callback($fields, $mockEntry);
    }

    /**
     * @return array
     */
    public function provideValidFieldsForElement()
    {
        $now = new DateTime();
        $tomorrow = new DateTime('+1 days');
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
            'sectionId' => null,
            'typeId' => null,
            'authorId' => 1,
            'postDate' => null,
            'expiryDate' => null,
            'parentId' => null,
            'revisionNotes' => null,
        );

        return array(
            'Basic attributes' => array(
                'fields' => array(
                    'title' => 'test',
                    'id' => 1,
                    'locale' => 'nl_nl',
                    'authorId' => 2,
                    'slug' => 'Test slug',
                    'postDate' => $now->getTimestamp(),
                    'expiryDate' => $tomorrow->getTimestamp(),
                    'enabled' => '0',
                ),
                'expectedAttributes' => array_merge($defaultExpectedAttributes, array(
                    'id' => 1,
                    'enabled' => false,
                    'locale' => 'nl_nl',
                    'slug' => 'test-slug',
                    'authorId' => 2,
                    'postDate' => $now,
                    'expiryDate' => $tomorrow,
                )),
            ),
            'No author given' => array(
                'fields' => array(),
                'expectedAttributes' => $defaultExpectedAttributes,
            ),
            'String author given' => array(
                'fields' => array(
                    'authorId' => 'String author',
                ),
                'expectedAttributes' => $defaultExpectedAttributes,
            ),
            'Parent given' => array(
                'fields' => array(
                    'parentId' => 'news',
                ),
                'expectedAttributes' => array_merge($defaultExpectedAttributes, array(
                    'parentId' => 1,
                )),
            ),
            'Ancestors given' => array(
                'fields' => array(
                    'ancestors' => 'news and stuff',
                ),
                'expectedAttributes' => array_merge($defaultExpectedAttributes, array(
                    'parentId' => 2,
                )),
            ),
        );
    }

    /**
     * @param string $sectionType
     *
     * @return MockObject|SectionModel
     */
    private function getMockSection($sectionType)
    {
        $mockSection = $this->getMockBuilder('Craft\SectionModel')
            ->disableOriginalConstructor()
            ->getMock();
        $mockSection->expects($this->exactly(1))->method('__get')->with('type')->willReturn($sectionType);

        return $mockSection;
    }

    /**
     * @param MockObject[] $mockEditableSections
     */
    private function setMockSectionsService(array $mockEditableSections)
    {
        $mockSectionsService = $this->getMock('Craft\SectionsService');
        $mockSectionsService->expects($this->exactly(1))
            ->method('getEditableSections')->willReturn($mockEditableSections);
        $this->setComponent(craft(), 'sections', $mockSectionsService);
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
     * @return MockObject|EntryModel
     */
    private function getMockEntry()
    {
        $mockEntry = $this->getMockBuilder('Craft\EntryModel')
            ->disableOriginalConstructor()
            ->getMock();
        $mockContent = $this->getMockBuilder('Craft\BaseModel')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())->method('getContent')->willReturn($mockContent);

        return $mockEntry;
    }

    /**
     * @param MockObject $mockEntry
     * @param bool       $success
     */
    private function setMockEntriesServiceSave(MockObject $mockEntry, $success)
    {
        $entriesService = $this->getMock('Craft\EntriesService');
        $entriesService->expects($this->exactly(1))->method('saveEntry')->with($mockEntry)->willReturn($success);
        $this->setComponent(craft(), 'entries', $entriesService);
    }

    /**
     * Set mock user session.
     */
    private function setMockUserSession()
    {
        $mockUserSession = $this->getMock('Craft\UserSessionService');
        $this->setComponent(craft(), 'userSession', $mockUserSession);
        $mockUserSession->expects($this->exactly(1))->method('getUser')->willReturn(null);
    }

    /**
     * @param int $authorId
     */
    private function setMockUsersService($authorId)
    {
        $mockUsersService = $this->getMock('Craft\UsersService');
        $mockUsersService->expects($this->exactly(1))->method('getUserByUsernameOrEmail')
            ->with($authorId)->willReturn(null);
        $this->setComponent(craft(), 'users', $mockUsersService);
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
