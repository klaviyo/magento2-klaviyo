<?php

namespace Klaviyo\Reclaim\Test\Unit\Observer;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Observer\KlaviyoUserObserver;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Authorization\Model\ResourceModel\Role\CollectionFactory as RoleCollectionFactory;
use Magento\Authorization\Model\ResourceModel\Role\Collection as RoleCollection;
use Magento\User\Model\UserFactory;
use Magento\User\Model\User;
use Magento\Authorization\Model\Acl\Role\Group as RoleGroup;
use \Magento\Framework\Event\Observer;

class KlaviyoUserObserverTest extends TestCase
{
    /**
     * @var KlaviyoUserObserver
     */
    protected $object;

    const KLAVIYO_USERNAME = 'Klaviyo';
    const KLAVIYO_PASSWORD = 'password';
    const KLAVIYO_EMAIL = 'test@example.com';
    const ADMIN_INFO = [
        'role_id' => 4,
        'username' => self::KLAVIYO_USERNAME,
        'firstname' => KlaviyoUserObserver::KLAVIYO_FIRST_NAME,
        'lastname'    => KlaviyoUserObserver::KLAVIYO_LAST_NAME,
        'email'     => self::KLAVIYO_EMAIL,
        'password'  => self::KLAVIYO_PASSWORD,
        'interface_locale' => KlaviyoUserObserver::DEFAULT_LOCALE,
        'is_active' => 1
    ];
    const AVAILABLE_ROLES = array(
        array(
            'role_name' => 'Not Klaviyo',
            'role_type' => RoleGroup::ROLE_TYPE,
            'role_id' => 3
        ),
        array(
            'role_name' => KlaviyoUserObserver::KLAVIYO_ROLE_NAME,
            'role_type' => RoleGroup::ROLE_TYPE,
            'role_id' => 4
        )
    );

    protected function setUp()
    {
        $scopeSettingMock = $this->createMock(ScopeSetting::class);
        $scopeSettingMock->method('getKlaviyoUsername')->willReturn(self::KLAVIYO_USERNAME);
        $scopeSettingMock->method('getKlaviyoPassword')->willReturn(self::KLAVIYO_PASSWORD);
        $scopeSettingMock->method('getKlaviyoEmail')->willReturn(self::KLAVIYO_EMAIL);
        $scopeSettingMock->method('unsetKlaviyoUsername')->willReturn(ScopeSetting::KLAVIYO_NAME_DEFAULT);
        $scopeSettingMock->method('unsetKlaviyoPassword')->willReturn('');
        $scopeSettingMock->method('unsetKlaviyoEmail')->willReturn('');

        $messageManagerMock = $this->createMock(MessageManager::class);

        $roleCollectionMock = $this->createMock(RoleCollection::class);
        $roleCollectionMock->method('getData')->willReturn(self::AVAILABLE_ROLES);
        $roleCollectionFactoryMock = $this->getMockBuilder(RoleCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $roleCollectionFactoryMock->method('create')->willReturn($roleCollectionMock);

        $userFactoryMock = $this->getMockBuilder(UserFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $userMock = $this->createMock(User::class);
        $userMock->method('setData')
            ->with($this->equalTo(self::ADMIN_INFO))
            ->willReturn('');
        $userMock->method('save')->willReturn($userMock);
        $userFactoryMock->method('create')->willReturn($userMock);


        $this->object = new KlaviyoUserObserver(
            $scopeSettingMock,
            $messageManagerMock,
            $roleCollectionFactoryMock,
            $userFactoryMock
        );
    }

    public function testKlaviyoUserObserverInstance()
    {
        $this->assertInstanceOf(KlaviyoUserObserver::class, $this->object);
    }

    public function testExecute()
    {
        $didNotFail = TRUE;
        
        $observerMock = $this->createMock(Observer::class);

        try {
            $this->object->execute($observerMock);
        } catch (\Exception $ex) {
            $didNotFail = FALSE;
        }

        $this->assertTrue($didNotFail);
    }
}