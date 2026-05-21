<?php

namespace Klaviyo\Reclaim\Observer;

use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\KlaviyoV3Sdk\Exception\KlaviyoApiException;
use Klaviyo\Reclaim\KlaviyoV3Sdk\Exception\KlaviyoResourceConflictException;
use Klaviyo\Reclaim\KlaviyoV3Sdk\KlaviyoV3Api;
use Klaviyo\Reclaim\Util\PhoneFormatter;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SaveOrderMarketingConsent implements ObserverInterface
{
    /**
     * @var ScopeSetting
     */
    protected $_klaviyoScopeSetting;

    /**
     * @var Logger
     */
    protected $_klaviyoLogger;

    /**
     * @var PhoneFormatter
     */
    protected $_phoneFormatter;

    /**
     * @param Logger $klaviyoLogger
     * @param ScopeSetting $klaviyoScopeSetting
     * @param PhoneFormatter $phoneFormatter
     */
    public function __construct(
        Logger $klaviyoLogger,
        ScopeSetting $klaviyoScopeSetting,
        PhoneFormatter $phoneFormatter
    ) {
        $this->_klaviyoLogger = $klaviyoLogger;
        $this->_klaviyoScopeSetting = $klaviyoScopeSetting;
        $this->_phoneFormatter = $phoneFormatter;
    }

    /**
     * Creates the KlaviyoV3Api client. Extracted to allow test subclasses to inject a mock.
     *
     * @param int|null $storeId
     * @return KlaviyoV3Api
     */
    protected function buildKlaviyoV3Api($storeId = null): KlaviyoV3Api
    {
        return new KlaviyoV3Api(
            $this->_klaviyoScopeSetting->getPublicApiKey($storeId),
            $this->_klaviyoScopeSetting->getPrivateApiKey($storeId),
            $this->_klaviyoScopeSetting,
            $this->_klaviyoLogger
        );
    }

    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();

        $order->setData('kl_sms_consent', json_encode($quote->getKlSmsConsent()));
        $order->setData('kl_email_consent', json_encode($quote->getKlEmailConsent()));

        $storeId = $quote->getStoreId();
        $email = $quote->getCustomerEmail();

        if ($quote->getKlEmailConsent() && $this->_klaviyoScopeSetting->getConsentAtCheckoutEmailIsActive($storeId)) {
            $listId = $this->_klaviyoScopeSetting->getConsentAtCheckoutEmailListId($storeId);
            $emailProfileObject = [
                'type' => 'profile',
                'attributes' => [
                    'email' => $email,
                    'subscriptions' => [
                        'email' => [
                            'marketing' => [
                                'consent' => 'SUBSCRIBED',
                            ],
                        ],
                    ],
                ],
            ];
            try {
                $api = $this->buildKlaviyoV3Api($storeId);
                $api->subscribeMembersToList($listId, [$emailProfileObject]);
            } catch (KlaviyoApiException $e) {
                $this->_klaviyoLogger->log(sprintf('[SaveOrderMarketingConsent] Email subscribe failed: %s', $e->getMessage()));
            } catch (KlaviyoResourceConflictException $e) {
                $this->_klaviyoLogger->log(sprintf('[SaveOrderMarketingConsent] Email subscribe conflict: %s', $e->getMessage()));
            }
        }

        if ($quote->getKlSmsConsent() && $this->_klaviyoScopeSetting->getMobileConsentIsActive($storeId)) {
            $mobileListId = $this->_klaviyoScopeSetting->getMobileConsentListId($storeId);
            $mobileSubscriptions = [];

            if ($this->_klaviyoScopeSetting->isMobileChannelEnabled($storeId, 'sms')) {
                $mobileSubscriptions['sms'] = [
                    'marketing' => [
                        'consent' => 'SUBSCRIBED',
                    ],
                ];
            }

            if ($this->_klaviyoScopeSetting->isMobileChannelEnabled($storeId, 'whatsapp')) {
                $mobileSubscriptions['whatsapp'] = [
                    'marketing' => [
                        'consent' => 'SUBSCRIBED',
                    ],
                ];
            }

            if (!empty($mobileSubscriptions)) {
                $shippingInfo = $quote->getShippingAddress();
                $rawPhone = $shippingInfo ? $shippingInfo->getTelephone() : null;
                $isoCountry = $shippingInfo ? $shippingInfo->getCountryId() : null;
                $e164Phone = $this->_phoneFormatter->formatE164($rawPhone, $isoCountry);

                if ($e164Phone === null) {
                    $this->_klaviyoLogger->log(sprintf(
                        '[SaveOrderMarketingConsent] Mobile subscribe skipped: phone could not be normalized to E.164 for store %s',
                        $storeId
                    ));
                } else {
                    $mobileProfileObject = [
                        'type' => 'profile',
                        'attributes' => [
                            'email' => $email,
                            'phone_number' => $e164Phone,
                            'subscriptions' => $mobileSubscriptions,
                        ],
                    ];
                    try {
                        $api = $this->buildKlaviyoV3Api($storeId);
                        $api->subscribeMembersToList($mobileListId, [$mobileProfileObject]);
                    } catch (KlaviyoApiException $e) {
                        $this->_klaviyoLogger->log(sprintf('[SaveOrderMarketingConsent] Mobile subscribe failed: %s', $e->getMessage()));
                    } catch (KlaviyoResourceConflictException $e) {
                        $this->_klaviyoLogger->log(sprintf('[SaveOrderMarketingConsent] Mobile subscribe conflict: %s', $e->getMessage()));
                    }
                }
            }
        }

        return $this;
    }
}
