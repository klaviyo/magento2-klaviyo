<?php

namespace Klaviyo\Reclaim\Helper;

use Klaviyo\Reclaim\KlaviyoV3Sdk\Exception\KlaviyoApiException;
use Klaviyo\Reclaim\KlaviyoV3Sdk\Exception\KlaviyoResourceConflictException;
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

    /**
     * Creates the KlaviyoV3Api client. Extracted to allow test subclasses to inject a mock.
     *
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

    public function getKlaviyoLists()
    {
        try {
            $api = $this->buildKlaviyoV3Api();
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

        $api = $this->buildKlaviyoV3Api();

        try {
            if ($optInSetting == ScopeSetting::API_SUBSCRIBE) {
                // Subscribe profile using the profile creation endpoint for lists
                $consent_profile_object = array(
                    'type' => 'profile',
                    'attributes' => array(
                        'email' => $email,
                        'subscriptions' => array(
                            'email' => array(
                                'marketing' => array(
                                    "consent" => "SUBSCRIBED"
                                )
                            )
                        )
                    )
                );

                $api->subscribeMembersToList($listId, array($consent_profile_object));
            } else {
                $existing_profile = $api->searchProfileByEmail($email);
                if (!$existing_profile) {
                    // Profile does not yet exist — create it, then add to list.
                    // if the profile gets created in the meantime, use the duplicate profile id on the 409 response
                    try {
                        $new_profile = $api->createProfile($properties);
                        $api->addProfileToList($listId, $new_profile["profile_id"]);
                    } catch (KlaviyoResourceConflictException $e) {
                        $duplicate_profile_id = $e->getDuplicateProfileId();
                        if ($duplicate_profile_id) {
                            $this->_klaviyoLogger->log(sprintf(
                                '[newsletter_race_condition_handled] Profile already existed for %s (duplicate_profile_id: %s). Proceeding with list subscription.',
                                $email,
                                $duplicate_profile_id
                            ));
                            $api->addProfileToList($listId, $duplicate_profile_id);
                        } else {
                            // 409 without a duplicate_profile_id is unexpected — fall back to search.
                            $this->_klaviyoLogger->log(sprintf(
                                '[newsletter_race_condition_handled] Profile already existed for %s but response lacked duplicate_profile_id. Falling back to profile search.',
                                $email
                            ));
                            $fallback_profile = $api->searchProfileByEmail($email);
                            if ($fallback_profile) {
                                $api->addProfileToList($listId, $fallback_profile["profile_id"]);
                            } else {
                                throw $e;
                            }
                        }
                    }
                } else {
                    // Profile already exists — use its ID to add to list.
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
        $api = $this->buildKlaviyoV3Api();
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

        $api = $this->buildKlaviyoV3Api($storeId);
        return $api->track($params);
    }

    /**
     * Get the external catalog ID for an event. This is used to link events to a specific scoped catalog in Klaviyo, so that
     * profile interest events can be connected to a specific scoped product when building flow audiences.
     *
     * @param int $website_id
     * @param int $store_id
     * @return string
     */
    public function getExternalCatalogIdForEvent($website_id, $store_id)
    {
        return $website_id . '-' . $store_id;
    }
}
