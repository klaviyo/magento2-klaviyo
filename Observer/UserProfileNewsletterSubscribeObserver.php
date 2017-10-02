<?php

namespace Klaviyo\Reclaim\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class UserProfileNewsletterSubscribeObserver implements ObserverInterface
{
    protected $data_helper;
    protected $customer_repository_interface;

    public function __construct(
        \Klaviyo\Reclaim\Helper\Data $data_helper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Customer\Api\CustomerRepositoryInterface $customer_repository_interface
    ) {
        $this->data_helper = $data_helper;
        $this->request = $request;
        $this->customer_repository_interface = $customer_repository_interface;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->data_helper->getEnabled()) return;

        $subscriber = $observer->getDataObject();

        if ($subscriber->isStatusChanged()) {
          $customer = $this->customer_repository_interface->getById($subscriber->getCustomerId());

          if ($subscriber->isSubscribed()) {
            $this->data_helper->subscribeEmailToKlaviyoList(
                $customer->getEmail(),
                $customer->getFirstname(),
                $customer->getLastname()
            );
          } else {
            $this->data_helper->unsubscribeEmailFromKlaviyoList($customer->getEmail());
          }
        }
    }
}
