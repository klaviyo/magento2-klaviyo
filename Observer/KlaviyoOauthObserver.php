<?php

namespace Klaviyo\Reclaim\Observer;

use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Integration\Model\IntegrationFactory;
use Magento\Integration\Model\AuthorizationService;
use Magento\Integration\Model\OauthService;


class KlaviyoOauthObserver implements ObserverInterface
{
    /**
     * Klaviyo scope setting helper
     * @var \Klaviyo\Reclaim\Helper\ScopeSetting $klaviyoScopeSetting
     */
    protected $_klaviyoScopeSetting;

    /**
     * @var ManagerInterface $messageManager
     */
    protected $_messageManager;

    /**
     * @var IntegrationFactory $integrationFactory
     */
    protected $_integrationFactory;


    /**
     * @param ScopeSetting $klaviyoScopeSetting
     * @param MessageManager $messageManager
     * @param IntegrationFactory $integrationFactory
     * @param AuthorizationService $authorizationService
     * @param OauthService $oauthService
     */
    public function __construct(
        ScopeSetting $klaviyoScopeSetting,
        MessageManager $messageManager,
        IntegrationFactory $integrationFactory,
        AuthorizationService $authorizationService,
        OauthService $oauthService
    ) {
        $this->_klaviyoScopeSetting = $klaviyoScopeSetting;
        $this->_messageManager = $messageManager;
        $this->_integrationFactory = $integrationFactory;
        $this->_authorizationService = $authorizationService;
        $this->_oauthService = $oauthService;
    }

    /*
     * @param Observer $observer
     * @return void
     * @throws Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (empty($this->_klaviyoScopeSetting->getPublicApiKey())) {
         throw new \Magento\Framework\Exception\StateException(__('To set up Oauth for Klaviyo, first save a <strong>Public Klaviyo API Key</strong> on the "General" tab.'));
        }
        try {
            $integrationData = array(
                'name' => $this->_klaviyoScopeSetting->getKlaviyoOauthName(),
                'status' => '0',
                'endpoint' => 'https://www.klaviyo.com/integrations/auth/magento_two?c=' . $this->_klaviyoScopeSetting->getPublicApiKey(),
                'identity_link_url' => 'https://www.klaviyo.com/integrations/redirect/magento_two',
                'setup_type' => '0'
            );

            $integration = $this->_integrationFactory->create()->setData($integrationData);
            $integration->save();

            $integrationId = $integration->getId();

            $consumerName = 'Integration' . $integrationId;

            // Code to create consumer
            $consumer = $this->_oauthService->createConsumer(['name' => $consumerName]);
            $consumerId = $consumer->getId();
            $integration->setConsumerId($consumerId);
            $integration->save();

            // Code to grant permission
            $this->_authorizationService->grantAllPermissions($integrationId);

            //reset the details in the store config
            $this->_klaviyoScopeSetting->unsetKlaviyoOauthName();
        }catch(Exception $e){
            throw new \Magento\Framework\Exception\StateException(__('Error creating Oauth Integration: '.$e->getMessage()));
        }

    }
}
