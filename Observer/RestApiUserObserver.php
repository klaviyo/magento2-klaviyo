<?php

namespace Klaviyo\Reclaim\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class RestApiUserObserver implements ObserverInterface
{
    /**
     * ManagerInterface
     * 
     * @var messageManager
     */
    protected $messageManager;

    /**
     * DataHelper
     * 
     * @var data_helper
     */
    protected $data_helper;

    /**
     * UserFactory
     * 
     * @var _userFactory
     */
    protected $_userFactory;

    /**
     * RoleCollectionFactory
     * 
     * @var RoleCollectionFactory
     */
    protected $_roleCollectionFactory;

    /**
     * Init
     *
     * @param MessageManager $messageManager
     * @param DataHelper $data_helper
     * @param UserFactory $_userFactory
     * @param RoleCollectionFactory $_roleCollectionFactory
     */
    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Klaviyo\Reclaim\Helper\Data $data_helper,
        \Magento\User\Model\UserFactory $userFactory,
        \Magento\Authorization\Model\ResourceModel\Role\CollectionFactory $roleCollectionFactory
    ) {
        $this->messageManager = $messageManager;
        $this->data_helper = $data_helper;
        $this->_userFactory = $userFactory;
        $this->_roleCollectionFactory = $roleCollectionFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $apiUsername = $this->data_helper->getRestApiUsername();
        $apiPassword = $this->data_helper->getRestApiPassword();
        $apiEmail = $this->data_helper->getRestApiEmail();

        //
        $role = $this->_roleCollectionFactory->create();
        $roleCollection = $role->getData();

        $adminInfo = [
            'role_id' => 1,
            'username' => $apiUsername,
            'firstname' => 'klaviyo',
            'lastname'    => 'klaviyo',
            'email'     => $apiEmail,
            'password'  => $apiPassword,
            'interface_locale' => 'en_US',
            'is_active' => 1
        ];

        //try to get the ID of the Klaviyo role
        try {
            foreach ($roleCollection as $item) {
                if ($item['role_name'] == 'Klaviyo' && $item['role_type'] == 'G') {
                     $adminInfo['role_id'] = $item['role_id'];
                }
            }
        } catch (\Exception $ex) {
            $this->messageManager->addErrorMessage('Unable to retrieve Klaviyo user role with error: ' . $ex->getMessage() . '\n Default administrator role used instead.');
        }

        //make the rest API user
        $userModel = $this->_userFactory->create();
        $userModel->setData($adminInfo);

        //try to save the new user
        try {
            $userModel->save();
            $this->messageManager->addSuccessMessage('REST User was successfully created');
        } catch (\Exception $ex) {
            $this->messageManager->addErrorMessage('Failed to create REST user with error: ' . $ex->getMessage());
        }
    }
}