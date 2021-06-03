<?php


namespace Klaviyo\Reclaim\Observer;

use Klaviyo\Reclaim\Helper\Data;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Plugin\Api\CartSearchRepository;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session;

class SalesQuoteSaveAfter implements ObserverInterface
{
    /**
     * Klaviyo Data Helper
     * @var Data $_dataHelper
     */
    protected $_dataHelper;

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
     * SalesQuoteSaveAfter constructor.
     * @param Data $dataHelper
     * @param ScopeSetting $scopeSetting
     * @param CartSearchRepository $cartSearchRepository
     * @param Session $checkoutsession
     */
    public function __construct(
        Data $dataHelper,
        ScopeSetting $scopeSetting,
        CartSearchRepository $cartSearchRepository,
        Session $checkoutsession
    )
    {
        $this->_dataHelper = $dataHelper;
        $this->_scopeSetting = $scopeSetting;
        $this->_cartSearchRepository = $cartSearchRepository;
        $this->_checkoutsession = $checkoutsession;
    }

    public function execute( Observer $observer )
    {
        $eventData = $observer->getData();
        $klAddedToCartPayload = $this->getCheckoutSession()->getKlAddedToCartKey();
        if ( !isset( $klAddedToCartPayload ) ) { return; }

        $public_key = $this->_scopeSetting->getPublicApiKey();
        if ( !isset( $public_key ) ) { return; }

        $kl_decoded_cookie = json_decode( base64_decode( $_COOKIE['__kla_id'] ), true );
        if ( !isset( $kl_decoded_cookie ) ) { return; };

        $kl_user_properties = array( '$email' => $kl_decoded_cookie['$email'] );
        if ( !isset( $kl_user_properties['$email'] ) ) { return; };

        $quote = $observer->getData('quote');
        $maskedQuoteId = $this->_cartSearchRepository->getMaskedIdFromQuoteId( $quote->getId() );
        $klAddedToCartPayload = array_merge( $klAddedToCartPayload, array( 'MaskedQuoteId' => $maskedQuoteId ) );

        $this->_dataHelper->klaviyoTrackEvent(
            'Added To Cart',
            $kl_user_properties,
            $klAddedToCartPayload
        );

        $this->getCheckoutSession()->unsKlAddedToCartKey();
    }

    public function getCheckoutSession()
    {
        return $this->_checkoutsession;
    }
}
