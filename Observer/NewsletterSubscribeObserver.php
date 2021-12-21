<?php

namespace Klaviyo\Reclaim\Observer;

use Klaviyo\Reclaim\Helper\Data;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Newsletter\Model\Subscriber;

class NewsletterSubscribeObserver implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var ScopeSetting
     */
    protected $scopeSetting;
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    public function __construct(
        Data $helper,
        ScopeSetting $scopeSetting,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->helper = $helper;
        $this->scopeSetting = $scopeSetting;
        $this->customerRepository = $customerRepository;
    }

    public function execute(Observer $observer)
    {
        if (!$this->scopeSetting->isEnabled()) {
            return;
        }

        /** @var Subscriber $subscriber */
        $subscriber = $observer->getDataObject();

        if ($subscriber && $subscriber->hasDataChanges()) {
            $customer = $this->getCustomer($subscriber);

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

    /**
     * @param Subscriber $subscriber
     * @return CustomerInterface|null
     */
    private function getCustomer(Subscriber $subscriber)
    {
        $customer = null;

        if ($subscriber->getCustomerId()) {
            try {
                $customer = $this->customerRepository->getById($subscriber->getCustomerId());
            } catch (NoSuchEntityException $e) {
                // If the customer doesn't exist - return null
            }
        }

        return $customer;
    }
}
