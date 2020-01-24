<?php

namespace Klaviyo\Reclaim\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class NewsletterSubscribeObserver implements ObserverInterface
{
    protected $_dataHelper;

    public function __construct(
        \Klaviyo\Reclaim\Helper\Data $_dataHelper,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->_dataHelper = $_dataHelper;
        $this->request = $request;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->_dataHelper->getEnabled()) return;

        $email = $this->request->getParam('email');
        $this->_dataHelper->subscribeEmailToKlaviyoList($email);
    }
}