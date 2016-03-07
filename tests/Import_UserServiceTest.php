<?php

namespace Craft;

use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Contains unit tests for the Import_UserService.
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 *
 * @coversDefaultClass Craft\Import_UserService
 * @covers ::<!public>
 */
class Import_UserServiceTest extends BaseTest
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
        require_once __DIR__.'/../services/Import_UserService.php';
    }

    /**
     * Setup mock localization service.
     */
    public function setUp()
    {
        $this->setMockLocalizationService();
    }

    /**
     * Import_UserService should implement IImportElementType.
     */
    public function testImportUserServiceShouldImplementIExportElementType()
    {
        $this->assertInstanceOf('Craft\IImportElementType', new Import_UserService());
    }

    /**
     * @covers ::getTemplate
     */
    public function testGetTemplateShouldReturnUserUploadTemplate()
    {
        $template = 'import/types/user/_upload';

        $service = new Import_UserService();
        $result = $service->getTemplate();

        $this->assertSame($template, $result);
    }

    /**
     * @covers ::getGroups
     */
    public function testGetGroupsShouldGetAllUserGroups()
    {
        $expectedResult = array('userGroup');

        $mockUserGroupsService = $this->getMock('Craft\UserGroupsService');
        $mockUserGroupsService->expects($this->exactly(1))->method('getAllGroups')->willReturn($expectedResult);
        $this->setComponent(craft(), 'userGroups', $mockUserGroupsService);

        $service = $this->getMockBuilder('Craft\Import_UserService')
            ->setMethods(array('getCraftEdition'))
            ->getMock();
        $service->expects($this->exactly(1))->method('getCraftEdition')->willReturn(Craft::Pro);

        $result = $service->getGroups();

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @covers ::getGroups
     */
    public function testGetGroupsShouldReturnTrueWhenNoUserGroupsFound()
    {
        $mockUserGroupsService = $this->getMock('Craft\UserGroupsService');
        $mockUserGroupsService->expects($this->exactly(1))->method('getAllGroups')->willReturn(array());
        $this->setComponent(craft(), 'userGroups', $mockUserGroupsService);

        $service = $this->getMockBuilder('Craft\Import_UserService')
            ->setMethods(array('getCraftEdition'))
            ->getMock();
        $service->expects($this->exactly(1))->method('getCraftEdition')->willReturn(Craft::Pro);

        $result = $service->getGroups();

        $this->assertTrue($result);
    }

    /**
     * @covers ::setModel
     */
    public function testSetModelShouldReturnUserModel()
    {
        $settings = array();

        $service = new Import_UserService();
        $result = $service->setModel($settings);

        $this->assertInstanceOf('Craft\UserModel', $result);
    }

    /**
     * @covers ::setCriteria
     */
    public function testSetCriteriaShouldSetElementVars()
    {
        $settings = array();

        $mockCriteria = $this->getMockCriteria();
        $mockCriteria->expects($this->exactly(2))->method('__set')
            ->withConsecutive(
                array('limit', null),
                array('status', null)
            );
        $this->setMockElementsService($mockCriteria);

        $service = new Import_UserService();
        $result = $service->setCriteria($settings);

        $this->assertInstanceOf('Craft\ElementCriteriaModel', $result);
    }

    /**
     * @covers ::delete
     */
    public function testDeleteShouldCallUsersDelete()
    {
        $mockUser = $this->getMockUser();
        $elements = array($mockUser);

        $usersService = $this->getMock('Craft\UsersService');
        $usersService->expects($this->exactly(1))->method('deleteUser')->with($mockUser)->willReturn(true);
        $this->setComponent(craft(), 'users', $usersService);

        $service = new Import_UserService();
        $result = $service->delete($elements);

        $this->assertTrue($result);
    }

    /**
     * @covers ::delete
     */
    public function testDeleteShouldReturnFalseWhenDeleteFails()
    {
        $mockUser = $this->getMockUser();
        $elements = array($mockUser);

        $usersService = $this->getMock('Craft\UsersService');
        $usersService->expects($this->exactly(1))->method('deleteUser')->with($mockUser)->willReturn(false);
        $this->setComponent(craft(), 'users', $usersService);

        $service = new Import_UserService();
        $result = $service->delete($elements);

        $this->assertFalse($result);
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
        $expectedCount = array_key_exists('photo', $fields) ? 1 : 0;
        $status = @$fields['status'];

        $service = new Import_UserService();
        $user = $service->prepForElementModel($fields, new UserModel());

        $this->assertTrue($user instanceof UserModel);
        $this->assertEquals($expectedAttributes, $user->getAttributes());
        $this->assertCount($expectedCount, $fields);

        if ($status) {
            $this->assertSame($status, $user->getStatus());
        }
    }

    /**
     * @covers ::prepForElementModel
     */
    public function testPrepForElementModelShouldUseEmailAsUsernameWhenSoConfigured()
    {
        $email = 'test@example.com';
        $fields = array(
            'username' => 'username',
            'email' => $email,
        );

        $mockConfigService = $this->getMock('Craft\ConfigService');
        $mockConfigService->expects($this->any())->method('get')->willReturn(true);
        $this->setComponent(craft(), 'config', $mockConfigService);

        $service = new Import_UserService();
        $user = $service->prepForElementModel($fields, new UserModel());

        $this->assertTrue($user instanceof UserModel);
        $this->assertSame($email, $user->email);
        $this->assertSame($email, $user->username);
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

        $service = new Import_UserService();
        $user = $service->prepForElementModel($fields, new UserModel());

        $this->assertTrue($user instanceof UserModel);
        $this->assertCount(2, $fields);
    }

    /**
     * Save should call users save.
     *
     * @covers ::save
     */
    public function testSaveShouldCallUsersSave()
    {
        $userId = 1;
        $settings = array(
            'elementvars' => array(
                'groups' => array(1, 2, 3),
            ),
        );

        $mockUser = $this->getMockUser();
        $mockUser->expects($this->exactly(1))->method('__get')->with('id')->willReturn($userId);

        $this->setMockUsersServiceSave($mockUser, true);

        $mockUserGroupsService = $this->getMock('Craft\UserGroupsService');
        $mockUserGroupsService->expects($this->exactly(1))->method('assignUserToGroups')
            ->with($userId, $settings['elementvars']['groups']);
        $this->setComponent(craft(), 'userGroups', $mockUserGroupsService);

        $service = new Import_UserService();
        $result = $service->save($mockUser, $settings);

        $this->assertTrue($result);
    }

    /**
     * Save should return false when saveUser fails.
     *
     * @covers ::save
     */
    public function testSaveShouldReturnFalseWhenSaveFails()
    {
        $settings = array();
        $mockUser = $this->getMockUser();

        $this->setMockUsersServiceSave($mockUser, false);

        $service = new Import_UserService();
        $result = $service->save($mockUser, $settings);

        $this->assertFalse($result);
    }

    /**
     * @covers ::callback
     */
    public function testCallbackShouldDoNothing()
    {
        $fields = array();
        $mockUser = $this->getMockUser();

        $service = new Import_UserService();
        $service->callback($fields, $mockUser);
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
            'username' => null,
            'photo' => null,
            'firstName' => null,
            'lastName' => null,
            'email' => null,
            'password' => null,
            'preferredLocale' => null,
            'weekStartDay' => 0,
            'admin' => false,
            'client' => false,
            'locked' => false,
            'suspended' => false,
            'pending' => false,
            'lastLoginDate' => null,
            'lastInvalidLoginDate' => null,
            'lockoutDate' => null,
            'passwordResetRequired' => false,
            'lastPasswordChangeDate' => null,
            'unverifiedEmail' => null,
            'newPassword' => null,
            'currentPassword' => null,
            'verificationCodeIssuedDate' => null,
            'invalidLoginCount' => null,
        );

        return array(
            'Basic attributes' => array(
                'fields' => array(
                    'id' => 1,
                    'username' => 'Bob',
                    'photo' => 'yes',
                    'firstName' => 'Bob',
                    'lastName' => 'de Bouwer',
                    'email' => 'bob.debouwer@tubbergen',
                    'preferredLocale' => 'nl_nl',
                    'newPassword' => 'welkom123',
                ),
                'expectedAttributes' => array_merge($defaultExpectedAttributes, array(
                    'id' => 1,
                    'username' => 'Bob',
                    'photo' => 'yes',
                    'firstName' => 'Bob',
                    'lastName' => 'de Bouwer',
                    'email' => 'bob.debouwer@tubbergen',
                    'preferredLocale' => 'nl_nl',
                    'newPassword' => 'welkom123',
                )),
            ),
            'Status active' => array(
                'fields' => array(
                    'status' => 'active',
                ),
                'expectedAttributes' => $defaultExpectedAttributes,
            ),
            'Status locked' => array(
                'fields' => array(
                    'status' => 'locked',
                ),
                'expectedAttributes' => array_merge($defaultExpectedAttributes, array(
                    'locked' => true,
                )),
            ),
            'Status suspended' => array(
                'fields' => array(
                    'status' => 'suspended',
                ),
                'expectedAttributes' => array_merge($defaultExpectedAttributes, array(
                    'suspended' => true,
                )),
            ),
            'Status Pending' => array(
                'fields' => array(
                    'status' => 'pending',
                ),
                'expectedAttributes' => array_merge($defaultExpectedAttributes, array(
                    'pending' => true,
                )),
            ),
            'Status Archived' => array(
                'fields' => array(
                    'status' => 'archived',
                ),
                'expectedAttributes' => array_merge($defaultExpectedAttributes, array(
                    'archived' => true,
                )),
            ),
        );
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
     * @return MockObject|UserModel
     */
    private function getMockUser()
    {
        $mockUser = $this->getMockBuilder('Craft\UserModel')
            ->disableOriginalConstructor()
            ->getMock();

        return $mockUser;
    }

    /**
     * @param MockObject $mockUser
     * @param bool       $success
     */
    private function setMockUsersServiceSave(MockObject $mockUser, $success)
    {
        $usersService = $this->getMock('Craft\UsersService');
        $usersService->expects($this->exactly(1))->method('saveUser')->with($mockUser)->willReturn($success);
        $this->setComponent(craft(), 'users', $usersService);
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
