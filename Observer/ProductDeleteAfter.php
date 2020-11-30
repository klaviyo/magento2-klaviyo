<?php

namespace Klaviyo\Reclaim\Observer;

use \Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Framework\Event\ObserverInterface;


class ProductDeleteAfter implements ObserverInterface
{
    /**
     * Klaviyo scope setting helper
     * @var \Klaviyo\Reclaim\Helper\ScopeSetting $klaviyoScopeSetting
     */
    protected $_klaviyoScopeSetting;

    /**
     * @var WebhookHelper
     */
    protected  $_webhookHelper;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param \Klaviyo\Reclaim\Helper\Webhook $_webhookHelper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param ScopeSetting $klaviyoScopeSetting
     */
    public function __construct(
        \Klaviyo\Reclaim\Helper\Webhook $_webhookHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        ScopeSetting $klaviyoScopeSetting
    ) {
        $this->_webhookHelper = $_webhookHelper;
        $this->_objectManager = $objectManager;
        $this->_klaviyoScopeSetting = $klaviyoScopeSetting;
    }

    /**
     * customer register event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->_klaviyoScopeSetting->getWebhookSecret() || !$this->_klaviyoScopeSetting->getProductDeleteAfterSetting()) {
            return;
        }
        $_product = $observer->getEvent()->getProduct();
        $this->_webhookHelper->makeWebhookRequest('product/delete', $_product->getData());
    }
}


