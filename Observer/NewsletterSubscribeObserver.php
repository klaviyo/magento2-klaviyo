<?php

namespace Klaviyo\Reclaim\Observer;

use Klaviyo\Reclaim\Helper\Data;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class NewsletterSubscribeObserver implements ObserverInterface
{
    protected $helper;
    protected $customerRepository;

    public function __construct(
        Data $helper,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->helper = $helper;
        $this->customerRepository = $customerRepository;
    }

    public function execute(Observer $observer)
    {
        if (!$this->helper->getEnabled()) {
            return;
        }

        $customer = null;
        $subscriber = $observer->getDataObject();

        if ($subscriber->isStatusChanged()) {
            if ($subscriber->getCustomerId()) {
                $customer = $this->customerRepository->getById($subscriber->getCustomerId());
            }

            if ($subscriber->isSubscribed()) {
                $this->helper->subscribeEmailToKlaviyoList(
                    $customer ? $customer->getEmail() : $subscriber->getEmail(),
                    $customer ? $customer->getFirstname() : $subscriber->getFirstname(),
                    $customer ? $customer->getLastname() : $subscriber->getLastname()
                );
            } else {
                $this->helper->unsubscribeEmailFromKlaviyoList(
                    $customer ? $customer->getEmail() : $subscriber->getEmail()
                );
            }
        }
    }
}
