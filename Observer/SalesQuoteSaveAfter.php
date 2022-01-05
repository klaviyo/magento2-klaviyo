<?php

namespace Klaviyo\Reclaim\Observer;

use Klaviyo\Reclaim\Helper\Data;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Model\Events;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Session;

class SalesQuoteSaveAfter implements ObserverInterface
{
    /**
     * Klaviyo Scope setting Helper
     * @var ScopeSetting
     */
    protected $_scopeSetting;

    /**
     * Magento Checkout Session
     * @var Data
     */
    protected $_dataHelper;

    /**
     * Events Model
     * @var Events
     */
    protected $_eventsModel;

    /**
     * Customer Session Model
     * @var Session
     */
    protected $_customerSession;

    /**
     * SalesQuoteSaveAfter constructor
     * @param ScopeSetting $scopeSetting
     * @param Data $dataHelper
     * @param Events $eventsModel
     * @param Session $customerSession
     */
    public function __construct(
        ScopeSetting $scopeSetting,
        Data $dataHelper,
        Events $eventsModel,
        Session $customerSession
    )
    {
        $this->_scopeSetting = $scopeSetting;
        $this->_dataHelper = $dataHelper;
        $this->_eventsModel = $eventsModel;
        $this->_customerSession = $customerSession;
    }

    public function execute(Observer $observer)
    {
        // Checking if the cookie is set here, if not it will return undefined and break the code
        if ( !isset($_COOKIE['__kla_id'] )) { return; }
        $kl_decoded_cookie = json_decode(base64_decode($_COOKIE['__kla_id']), true);

        // Get the custom variable set in the DataHelper object via the SalesQuoteProductAddAfter observer.
        // Check if the public key and Added to Cart payload are set
        $public_key = $this->_scopeSetting->getPublicApiKey();
        $klAddedToCartPayload = $this->_dataHelper->getObserverAtcPayload();
        if ( !isset($klAddedToCartPayload) or !isset($public_key)) { return; }

        // Make sure we have an identifier for the customer set in the cookie
        if ( isset($kl_decoded_cookie['$exchange_id'])) {
            $kl_user_properties = ['$exchange_id' => $kl_decoded_cookie['$exchange_id']];
        } elseif (isset($kl_decoded_cookie['$email'])) {
            $kl_user_properties = ['$email' => $kl_decoded_cookie['$email']];
        } else { return; }

        // Setting QuoteId at this point since the MaskedQuoteId is not updated when this event is dispatched,
        // MaskedQuoteId is set into the payload while the EventsTopic cron job moves rows into the Sync table
        $quote = $observer->getData('quote');
        $encodedCustomerId = $this->checkCustomerAndReturnEncodedId($quote);
        $klAddedToCartPayload['QuoteId'] = isset($encodedCustomerId) ? "kx_identifier_$encodedCustomerId" : $quote->getId();

        $newEvent = [
            'status' => 'NEW',
            'user_properties' => json_encode($kl_user_properties),
            'event'=> 'Added To Cart',
            'payload' => json_encode($klAddedToCartPayload)
        ];

        // Creating a new row in the kl_events table
        $eventsData = $this->_eventsModel->setData($newEvent);
        $eventsData->save();

        //Unset the custom variable set in DataHelper Object
        $this->_dataHelper->unsetObserverAtcPayload();
    }

    /**
     * Check if customer is logged in and return base64 encoded string for the ID
     * @param $quote
     * @return string|null
     */
    private function checkCustomerAndReturnEncodedId($quote) {
        if ($this->_customerSession->isLoggedIn()) {
            $customerId = $quote->getCustomer()->getId();
            return base64_encode($customerId);
        } else {
            return null;
        }
    }
}
