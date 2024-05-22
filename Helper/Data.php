<?php

namespace Klaviyo\Reclaim\Helper;

use Klaviyo\Reclaim\KlaviyoV3Sdk\KlaviyoV3Api;
use Magento\Framework\App\Helper\Context;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
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

    /**
     * V3 API Wrapper
     * @var KlaviyoV3Api $api
     */
    protected $api;

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

    public function getKlaviyoLists()
    {
        try {
            $api = new KlaviyoV3Api($this->_klaviyoScopeSetting->getPublicApiKey(), $this->_klaviyoScopeSetting->getPrivateApiKey(), $this->_klaviyoScopeSetting, $this->_klaviyoLogger);
            $lists_response = $api->getLists();
            $lists = array();

            foreach ($lists_response as $list) {
                $lists[] = array(
                    'id' => $list['id'],
                    'name' => $list['attributes']['name']
                );
            }

            return [
                'success' => true,
                'lists' => $lists
            ];
        } catch (\Exception $e) {
            $this->_klaviyoLogger->log(sprintf('Unable to get list: %s', $e->getMessage()));
            return [
                'success' => false,
                'reason' => $e->getMessage()
            ];
        }
    }

    /**
     * @param string $email
     * @param string|null $firstName
     * @param string|null $lastName
     * @param string|null $source
     * @return array|false|null|string
     */
    public function subscribeEmailToKlaviyoList($email, $firstName = null, $lastName = null)
    {
        $listId = $this->_klaviyoScopeSetting->getNewsletter();
        $optInSetting = $this->_klaviyoScopeSetting->getOptInSetting();

        $properties = [];
        $properties['email'] = $email;
        if ($firstName) {
            $properties['first_name'] = $firstName;
        }
        if ($lastName) {
            $properties['last_name'] = $lastName;
        }

        $api = new KlaviyoV3Api($this->_klaviyoScopeSetting->getPublicApiKey(), $this->_klaviyoScopeSetting->getPrivateApiKey(), $this->_klaviyoScopeSetting, $this->_klaviyoLogger);

        try {
            if ($optInSetting == ScopeSetting::API_SUBSCRIBE) {
                // Subscribe profile using the profile creation endpoint for lists
                $consent_profile_object = array(
                    'type' => 'profile',
                    'attributes' => array(
                        'email' => $email,
                        'subscriptions' => array(
                            'email' => [
                                'MARKETING'
                            ]
                        )
                    )
                );

                $api->subscribeMembersToList($listId, array($consent_profile_object));
            } else {
                // Search for profile by email using the api/profiles endpoint
                $existing_profile = $api->searchProfileByEmail($email);
                if (!$existing_profile) {
                    // If the profile exists, use the ID to add to a list
                    // If the profile does not exist, create
                    $new_profile = $api->createProfile($properties);
                    $api->addProfileToList($listId, $new_profile["profile_id"]);
                } else {
                    $profile_id = $existing_profile["profile_id"];
                    $api->addProfileToList($listId, $profile_id);
                }
            }
        } catch (\Exception $e) {
            $this->_klaviyoLogger->log(sprintf('Unable to subscribe %s to list %s: %s', $email, $listId, $e));
        }
    }

    /**
     * @param string $email
     * @return array|string|null
     */
    public function unsubscribeEmailFromKlaviyoList($email)
    {
        $api = new KlaviyoV3Api($this->_klaviyoScopeSetting->getPublicApiKey(), $this->_klaviyoScopeSetting->getPrivateApiKey(), $this->_klaviyoScopeSetting, $this->_klaviyoLogger);
        $listId = $this->_klaviyoScopeSetting->getNewsletter();
        try {
            $response = $api->unsubscribeEmailFromKlaviyoList($email, $listId);
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
            'event' => $event,
            'properties' => $properties,
            'customer_properties' => $customer_properties
        );

        if (!is_null($timestamp)) {
            $params['time'] = $timestamp;
        }

        $api = new KlaviyoV3Api($this->_klaviyoScopeSetting->getPublicApiKey($storeId), $this->_klaviyoScopeSetting->getPrivateApiKey($storeId), $this->_klaviyoScopeSetting, $this->_klaviyoLogger);
        return $api->track($params);
    }
}
