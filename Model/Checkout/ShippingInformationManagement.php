<?php


namespace Klaviyo\Reclaim\Model\Checkout;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Quote\Model\QuoteRepository;

class ShippingInformationManagement
{
    protected $quoteRepository;
    protected $addressRepository;

    public function __construct(
        QuoteRepository $quoteRepository,
        AddressRepositoryInterface $addressRepository
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->addressRepository = $addressRepository;
    }

    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {

        if(!$extAttributes = $addressInformation->getExtensionAttributes())
        {
            return;
        }

        $quote = $this->quoteRepository->getActive($cartId);

        $quote->setKlSmsConsent($extAttributes->getKlSmsConsent());
        $quote->setKlEmailConsent($extAttributes->getKlEmailConsent());

        $smsConsent = $extAttributes->getKlSmsConsent();
        $phoneNumber = $extAttributes->getKlSmsPhoneNumber();

        if ($smsConsent && $phoneNumber) {
           $quote->getShippingAddress()->setTelephone($phoneNumber);
           $customerAddressId = $quote->getShippingAddress()->getCustomerAddressId();
           if ($customerAddressId) {
               $address = $this->addressRepository->getById($customerAddressId);
               $address->setTelephone($phoneNumber);
               $this->addressRepository->save($address);
           }
        }
    }
}
