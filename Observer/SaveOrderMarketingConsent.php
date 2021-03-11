<?php


namespace Klaviyo\Reclaim\Observer;

use Exception;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Helper\Webhook;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;


class SaveOrderMarketingConsent implements ObserverInterface
{
    /**
     * Klaviyo scope setting helper
     * @var ScopeSetting $klaviyoScopeSetting
     */
    protected $_klaviyoScopeSetting;

    /**
     * @var Webhook $webhookHelper
     */
    protected  $_webhookHelper;

    /**
     * @param Webhook $webhookHelper
     * @param ScopeSetting $klaviyoScopeSetting
     */
    public function __construct(
        Webhook $webhookHelper,
        ScopeSetting $klaviyoScopeSetting
    ) {
        $this->_webhookHelper = $webhookHelper;
        $this->_klaviyoScopeSetting = $klaviyoScopeSetting;
    }

    /**
     * customer register event handler
     *
     * @param Observer $observer
     * @return void
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();

        $order->setData("kl_sms_consent", json_encode($quote->getKlSmsConsent()));
        $order->setData("kl_email_consent", json_encode($quote->getKlEmailConsent()));

        $shippingInfo = $quote->getShippingAddress();

        if (
            $this->_klaviyoScopeSetting->getWebhookSecret()
            && $this->_klaviyoScopeSetting->getConsentAtCheckoutSMSIsActive()
        ) {
            $data = array(
                "data" => array(
                    array(
                        "customer" => array(
                            "email" => $quote->getCustomerEmail(),
                            "country" => $shippingInfo->getCountry(),
                            "phone" => $shippingInfo->getTelephone(),
                        ),
                        "consent" => true,
                        "consent_type" => "sms",
                        "group_id" => $this->_klaviyoScopeSetting->getConsentAtCheckoutSMSListId(),
                        "updated_at" => $quote->getUpdatedAt(),

                    )
                )
            );
            $this->_webhookHelper->makeWebhookRequest('custom/consent', $data);
        }

        return $this;
    }
}
