<?php

namespace Klaviyo\Reclaim\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class UserProfileNewsletterSubscribeObserver implements ObserverInterface
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
        
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $om->get('Magento\Customer\Model\Session');
        $customer = $customerSession->getCustomer();

        $email = $customer->getEmail();
     
        $is_subscribed = $this->request->getParam('is_subscribed');

        if ($is_subscribed) {
            $this->data_helper->subscribeEmailToKlaviyoList(
                $email,
                $customer->getFirstname(),
                $customer->getLastname()
            );
        } else {
            $this->data_helper->unsubscribeEmailFromKlaviyoList($email);
        }
    }
}