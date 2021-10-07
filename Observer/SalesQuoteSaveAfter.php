<?php


namespace Klaviyo\Reclaim\Observer;

use Klaviyo\Reclaim\Helper\Data;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Model\Events;
use Klaviyo\Reclaim\Plugin\Api\CartSearchRepository;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session;

class SalesQuoteSaveAfter implements ObserverInterface
{
    /**
     * Klaviyo Scope setting Helper
     * @var ScopeSetting $_scopeSetting
     */
    protected $_scopeSetting;

    /**
     * Klaviyo Cart Search Interface
     * @var  CartSearchRepository $_cartSearchRepository
     */
    protected $_cartSearchRepository;

    /**
     * Magento Checkout Session
     * @var Session $_checkoutsession
     */
    protected $_checkoutsession;

    /**
     * Added To Cart Model
     * @var Events
     */
    protected $_eventsModel;

    /**
     * SalesQuoteSaveAfter Constructor
     * @param Data $dataHelper
     * @param ScopeSetting $scopeSetting
     * @param CartSearchRepository $cartSearchRepository
     * @param Session $checkoutsession
     * @param Events $eventsModel
     */
    public function __construct(
        ScopeSetting $scopeSetting,
        CartSearchRepository $cartSearchRepository,
        Session $checkoutsession,
        Events $eventsModel
    )
    {
        $this->_scopeSetting = $scopeSetting;
        $this->_cartSearchRepository = $cartSearchRepository;
        $this->_checkoutsession = $checkoutsession;
        $this->_eventsModel = $eventsModel;
    }

    public function execute( Observer $observer )
    {
        $klAddedToCartPayload = $this->getCheckoutSession()->getKlAddedToCartKey();
        if ( !isset( $klAddedToCartPayload ) ) { return; }

        $public_key = $this->_scopeSetting->getPublicApiKey();
        if ( !isset( $public_key ) ) { return; }

        if ( ! isset( $_COOKIE['__kla_id'] )) { return; }

        $kl_decoded_cookie = json_decode( base64_decode( $_COOKIE['__kla_id'] ), true );
        if ( !isset( $kl_decoded_cookie ) ) { return; }

        if ( isset( $kl_decoded_cookie['$exchange_id'] )) {
            $kl_user_properties = array('$exchange_id' => $kl_decoded_cookie['$exchange_id']);
        } elseif ( isset( $kl_decoded_cookie['$email'] )) {
            $kl_user_properties = array('$email' => $kl_decoded_cookie['$email']);
        } else { return; }

        $quote = $observer->getData('quote');
        $klAddedToCartPayload = array_merge( $klAddedToCartPayload, array( 'QuoteId' => $quote->getId() ) );

        $newEvent = [
            'status' => 'NEW',
            'user_properties' => json_encode( $kl_user_properties ),
            'event'=> 'Added To Cart',
            'payload' => json_encode( $klAddedToCartPayload )
        ];

        $eventsData = $this->_eventsModel->setData( $newEvent );
        $eventsData->save();

        $this->getCheckoutSession()->unsKlAddedToCartKey();
    }

    public function getCheckoutSession()
    {
        return $this->_checkoutsession;
    }
}
