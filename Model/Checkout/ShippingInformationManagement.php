<?php

namespace Klaviyo\Reclaim\Model\Checkout;

use Magento\Quote\Model\QuoteRepository;

class ShippingInformationManagement
{
    protected $quoteRepository;

    public function __construct(QuoteRepository $quoteRepository)
    {
        $this->quoteRepository = $quoteRepository;
    }

    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {

        if (!$extAttributes = $addressInformation->getExtensionAttributes()) {
            return;
        }

        $quote = $this->quoteRepository->getActive($cartId);

        $quote->setKlSmsConsent($extAttributes->getKlSmsConsent());
        $quote->setKlEmailConsent($extAttributes->getKlEmailConsent());
    }
}
