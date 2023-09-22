<?php

namespace Klaviyo\Reclaim\Helper;

use Klaviyo\Reclaim\KlaviyoV3Sdk\KlaviyoV3Api;
use Magento\Framework\App\Helper\Context;

class Data extends KlaviyoV3Api
{
    const USER_AGENT = 'Klaviyo/1.0';
    const KLAVIYO_HOST = 'https://a.klaviyo.com/';
    const LIST_V3_API = 'api/list';

    /**
     * Klaviyo logger helper
     * @var \Klaviyo\Reclaim\Helper\Logger $klaviyoLogger
     */
    protected $_klaviyoLogger;

    /**
     * Klaviyo scope setting helper
     * @var \Klaviyo\Reclaim\Helper\ScopeSetting $klaviyoScopeSetting
     */
    protected $_klaviyoScopeSetting;

    /**
     * Variable used for storage of klAddedToCartPayload between observers
     * @var
     */
    private $observerAtcPayload;

    public function __construct(
        Context $context,
        Logger $klaviyoLogger,
        ScopeSetting $klaviyoScopeSetting
    ) {
        parent::__construct($context);
        $this->_klaviyoLogger = $klaviyoLogger;
        $this->_klaviyoScopeSetting = $klaviyoScopeSetting;
        $this->observerAtcPayload = null;
    }

    public function getObserverAtcPayload()
    {
        return $this->observerAtcPayload;
    }

    public function setObserverAtcPayload($data)
    {
        $this->observerAtcPayload = $data;
    }

    public function unsetObserverAtcPayload()
    {
        $this->observerAtcPayload = null;
    }

    public function getKlaviyoLists($api_key = null)
    {
        $response = $this->getLists();

        usort($response, function ($a, $b) {
            return strtolower($a->list_name) > strtolower($b->list_name) ? 1 : -1;
        });

        return [
            'success' => true,
            'lists' => $response
        ];
    }

    /**
     * @param string $email
     * @param string|null $firstName
     * @param string|null $lastName
     * @param string|null $source
     * @return array|false|null|string
     */
    public function subscribeEmailToKlaviyoList($email, $firstName = null, $lastName = null, $source = null)
    {
        $listId = $this->_klaviyoScopeSetting->getNewsletter();
        $optInSetting = $this->_klaviyoScopeSetting->getOptInSetting();

        $properties = [];
        $properties['email'] = $email;
        if ($firstName) {
            $properties['$first_name'] = $firstName;
        }
        if ($lastName) {
            $properties['$last_name'] = $lastName;
        }
        if ($source) {
            $properties['$source'] = $source;
        }
        if ($optInSetting == ScopeSetting::API_SUBSCRIBE) {
            $properties['$consent'] = ['email'];
        }

        $propertiesVal = ['profiles' => $properties];

        if ($optInSetting == ScopeSetting::API_SUBSCRIBE) {
            $path = self::LIST_V3_API . $listId . $optInSetting;
        } else {
            $path = self::LIST_V3_API . $optInSetting;
        }
        try {
            $response = $this->subscribeMembersToList($path, $listId, $propertiesVal);
        } catch (\Exception $e) {
            $this->_klaviyoLogger->log(sprintf('Unable to subscribe %s to list %s: %s', $email, $listId, $e));
            $response = false;
        }

        return $response;
    }

    /**
     * @param string $email
     * @return array|string|null
     */
    public function unsubscribeEmailFromKlaviyoList($email)
    {
        $listId = $this->_klaviyoScopeSetting->getNewsletter();
        try {
            $response = $this->unsubscribeEmailFromKlaviyoList($email);
        } catch (\Exception $e) {
            $this->_klaviyoLogger->log(sprintf('Unable to unsubscribe %s from list %s: %s', $email, $listId, $e));
            $response = false;
        }

        return $response;
    }

    public function klaviyoTrackEvent($event, $customer_properties = [], $properties = [], $timestamp = null, $storeId = null)
    {
        if (
            (!array_key_exists('$email', $customer_properties) || empty($customer_properties['$email']))
            && (!array_key_exists('$id', $customer_properties) || empty($customer_properties['$id']))
            && (!array_key_exists('$exchange_id', $customer_properties) || empty($customer_properties['$exchange_id']))
        ) {
            return 'You must identify a user by email or ID.';
        }
        $params = array(
            'metric' => $event,
            'properties' => $properties,
            'customer_properties' => $customer_properties
        );

        if (!is_null($timestamp)) {
            $params['time'] = $timestamp;
        }
        return $this->track($params);
    }
}
