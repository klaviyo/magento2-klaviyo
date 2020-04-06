<?php

namespace Klaviyo\Reclaim\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class NewsletterSubscribeObserver implements ObserverInterface
{
    protected $_dataHelper;
    protected $_klaviyoScopeSetting;

    public function __construct(
        \Klaviyo\Reclaim\Helper\Data $_dataHelper,
        \Klaviyo\Reclaim\Helper\ScopeSetting $_klaviyoScopeSetting,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->_dataHelper = $_dataHelper;
        $this->_klaviyoScopeSetting = $_klaviyoScopeSetting;
        $this->request = $request;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->_klaviyoScopeSetting->isEnabled()) return;

        $email = $this->request->getParam('email');
        $this->_dataHelper->subscribeEmailToKlaviyoList($email);
    }
}