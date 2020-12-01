<?php

namespace Klaviyo\Reclaim\Observer;

use Exception;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Helper\Webhook;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\ObjectManagerInterface;


class ProductDeleteAfter implements ObserverInterface
{
    /**
     * Klaviyo scope setting helper
     * @var ScopeSetting $klaviyoScopeSetting
     */
    protected $_klaviyoScopeSetting;

    /**
     * @var Webhook $webhookHelper
     */
    protected  $_webhookHelper;

    /**
     * @var ObjectManagerInterface $objectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param Webhook $webhookHelper
     * @param ObjectManagerInterface $objectManager
     * @param ScopeSetting $klaviyoScopeSetting
     */
    public function __construct(
        Webhook $webhookHelper,
        ObjectManagerInterface $objectManager,
        ScopeSetting $klaviyoScopeSetting
    ) {
        $this->_webhookHelper = $webhookHelper;
        $this->_objectManager = $objectManager;
        $this->_klaviyoScopeSetting = $klaviyoScopeSetting;
    }

    /**
     * customer register event handler
     *
     * @param Observer $observer
     * @return void
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        if (!$this->_klaviyoScopeSetting->getWebhookSecret() || !$this->_klaviyoScopeSetting->getProductDeleteAfterSetting()) {
            return;
        }
        $_product = $observer->getEvent()->getProduct();
        $this->_webhookHelper->makeWebhookRequest('product/delete', $_product->getData());
    }
}


