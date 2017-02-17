<?php

namespace Klaviyo\Reclaim\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class NewsletterSubscribeObserver implements ObserverInterface
{
    protected $data_helper;

    public function __construct(
        \Klaviyo\Reclaim\Helper\Data $data_helper,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->data_helper = $data_helper;
        $this->request = $request;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->data_helper->getEnabled()) return;

        $email = $this->request->getParam('email');
        $this->data_helper->subscribeEmailToKlaviyoList($email);
    }
}