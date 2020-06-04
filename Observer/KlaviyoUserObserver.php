<?php

namespace Klaviyo\Reclaim\Observer;

use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Authorization\Model\ResourceModel\Role\CollectionFactory;
use Magento\User\Model\UserFactory;
use Magento\Authorization\Model\Acl\Role\Group as RoleGroup;

class KlaviyoUserObserver implements ObserverInterface
{
    /**
     * 
     * @var \Klaviyo\Reclaim\Helper\ScopeSetting $klaviyoScopeSetting
     */
     protected $_klaviyoScopeSetting;

     /**
     * ManagerInterface
     * 
     * @var ManagerInterface $messageManager
     */
    protected $_messageManager;

    /**
     * RoleCollectionFactory
     * 
     * @var RoleCollectionFactory $roleCollectionFactory
     */
    protected $_roleCollectionFactory;

    /**
     * UserFactory
     * 
     * @var UserFactory $userFactory
     */
    protected $_userFactory;

    const KLAVIYO_FIRST_NAME = 'klaviyo';
    const KLAVIYO_LAST_NAME = 'klaviyo';
    const KLAVIYO_ROLE_NAME = 'Klaviyo';
    const DEFAULT_LOCALE = 'en_US';

    /**
     * Init
     *
     * @param ScopeSetting $klaviyoScopeSetting
     * @param MessageManager $messageManager
     * @param RoleCollectionFactory $roleCollectionFactory
     * @param UserFactory $userFactory
     */
    public function __construct(
        ScopeSetting $klaviyoScopeSetting,
        MessageManager $messageManager,
        CollectionFactory $roleCollectionFactory,
        UserFactory $userFactory
    ) {
        $this->_klaviyoScopeSetting = $klaviyoScopeSetting;
        $this->_messageManager = $messageManager;
        $this->_roleCollectionFactory = $roleCollectionFactory;
        $this->_userFactory = $userFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $apiUsername = $this->_klaviyoScopeSetting->getKlaviyoUsername();
        $apiPassword = $this->_klaviyoScopeSetting->getKlaviyoPassword();
        $apiEmail = $this->_klaviyoScopeSetting->getKlaviyoEmail();

        //
        $role = $this->_roleCollectionFactory->create();
        $roleCollection = $role->getData();

        $adminInfo = [
            'role_id' => 1,
            'username' => $apiUsername,
            'firstname' => self::KLAVIYO_FIRST_NAME,
            'lastname'    => self::KLAVIYO_LAST_NAME,
            'email'     => $apiEmail,
            'password'  => $apiPassword,
            'interface_locale' => self::DEFAULT_LOCALE,
            'is_active' => 1
        ];

        //try to get the ID of the Klaviyo role
        try {
            foreach ($roleCollection as $item) {
                if ($item['role_name'] == self::KLAVIYO_ROLE_NAME && $item['role_type'] == RoleGroup::ROLE_TYPE) {
                     $adminInfo['role_id'] = $item['role_id'];
                }
            }
        } catch (\Exception $ex) {
            $this->_messageManager->addErrorMessage('Unable to retrieve Klaviyo user role with error: ' . $ex->getMessage() . '\n Default administrator role used instead.');
        }

        //make the rest API user
        $userModel = $this->_userFactory->create();
        $userModel->setData($adminInfo);

        //try to save the new user
        try {
            $userModel->save();
            $this->_messageManager->addSuccessMessage('Klaviyo User was successfully created');
        } catch (\Exception $ex) {
            $this->_messageManager->addErrorMessage('Failed to create Klaviyo user with error: ' . $ex->getMessage());
        }

        //reset the details in the store config
        $this->_klaviyoScopeSetting->unsetKlaviyoUsername();
        $this->_klaviyoScopeSetting->unsetKlaviyoPassword();
        $this->_klaviyoScopeSetting->unsetKlaviyoEmail();
    }
}
