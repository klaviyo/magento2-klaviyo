<?php

namespace Klaviyo\Reclaim\Observer;

use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Integration\Model\AuthorizationService;
use Magento\Integration\Model\IntegrationFactory;
use Magento\Integration\Model\OauthService;

class KlaviyoOAuthObserver implements ObserverInterface
{
    /**
     * Klaviyo scope setting helper
     * @var Klaviyo\Reclaim\Helper\ScopeSetting $klaviyoScopeSetting
     */
    protected $_klaviyoScopeSetting;


    /**
     * @var Magento\Integration\Model\IntegrationFactory $integrationFactory
     */
    protected $_integrationFactory;

    /**
     * @var Magento\Integration\Model\AuthorizationService $authorizationService
     */
    protected $_authorizationService;

    /**
     * @var Magento\Integration\Model\OauthService $oauthService
     */
    protected $_oauthService;

    /**
     * @param Klaviyo\Reclaim\Helper\ScopeSetting $klaviyoScopeSetting
     * @param Magento\Integration\Model\IntegrationFactory $integrationFactory
     * @param Magento\Integration\Model\AuthorizationService $authorizationService
     * @param Magento\Integration\Model\OauthService $oauthService
     */
    public function __construct(
        ScopeSetting $klaviyoScopeSetting,
        IntegrationFactory $integrationFactory,
        AuthorizationService $authorizationService,
        OauthService $oauthService
    ) {
        $this->_klaviyoScopeSetting = $klaviyoScopeSetting;
        $this->_integrationFactory = $integrationFactory;
        $this->_authorizationService = $authorizationService;
        $this->_oauthService = $oauthService;
    }

    /**
     * @param Magento\Framework\Event\Observer $observer
     * @return void
     * @throws Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (empty($this->_klaviyoScopeSetting->getPublicApiKey())) {
            throw new \Magento\Framework\Exception\StateException(__('To set up Oauth for Klaviyo, first obtain a <strong>Public Klaviyo API Key</strong> using instructions from <a href="https://help.klaviyo.com/hc/en-us/articles/115005062267-Manage-Your-Account-s-API-Keys#your-public-api-key-site-id2">here</a> and save it on the "General" tab.'));
        }
        try {
            $integrationData = array(
                'name' => $this->_klaviyoScopeSetting->getKlaviyoOauthName(),
                'status' => '0',
                'endpoint' => 'https://www.klaviyo.com/integration-oauth-one/magento-two/auth/confirm?c=' . $this->_klaviyoScopeSetting->getPublicApiKey(),
                'identity_link_url' => 'https://www.klaviyo.com/integration-oauth-one/magento-two/auth/handle',
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
        } catch (Exception $e) {
            throw new \Magento\Framework\Exception\StateException(__('Error creating OAuth Integration: ' . $e->getMessage()));
        }
    }
}
