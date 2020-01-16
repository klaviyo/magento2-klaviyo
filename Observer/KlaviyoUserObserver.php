<?php

namespace Klaviyo\Reclaim\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Authorization\Model\Acl\Role\Group as RoleGroup;

class KlaviyoUserObserver implements ObserverInterface
{
    /**
     * DataHelper
     * 
     * @var _dataHelper
     */
     protected $_dataHelper;

     /**
     * ManagerInterface
     * 
     * @var _messageManager
     */
    protected $_messageManager;

    /**
     * RoleCollectionFactory
     * 
     * @var RoleCollectionFactory
     */
    protected $_roleCollectionFactory;

        /**
     * UserFactory
     * 
     * @var _userFactory
     */
    protected $_userFactory;

    const KLAVIYO_FIRST_NAME = 'klaviyo';
    const KLAVIYO_LAST_NAME = 'klaviyo';
    const KLAVIYO_ROLE_NAME = 'Klaviyo';
    const DEFAULT_LOCALE = 'en_US';

    /**
     * Init
     *
     * @param DataHelper $_dataHelper
     * @param MessageManager $_messageManager
     * @param RoleCollectionFactory $_roleCollectionFactory
     * @param UserFactory $_userFactory
     */
    public function __construct(
        \Klaviyo\Reclaim\Helper\Data $_dataHelper,
        \Magento\Framework\Message\ManagerInterface $_messageManager,
        \Magento\Authorization\Model\ResourceModel\Role\CollectionFactory $_roleCollectionFactory,
        \Magento\User\Model\UserFactory $_userFactory
    ) {
        $this->_dataHelper = $_dataHelper;
        $this->_messageManager = $_messageManager;
        $this->_roleCollectionFactory = $_roleCollectionFactory;
        $this->_userFactory = $_userFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $apiUsername = $this->_dataHelper->getKlaviyoUsername();
        $apiPassword = $this->_dataHelper->getKlaviyoPassword();
        $apiEmail = $this->_dataHelper->getKlaviyoEmail();

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
        $this->_dataHelper->unsetKlaviyoUsername();
        $this->_dataHelper->unsetKlaviyoPassword();
        $this->_dataHelper->unsetKlaviyoEmail();
    }
}