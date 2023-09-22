<?php

namespace Klaviyo\Reclaim\KlaviyoV3Sdk;

use DateTime;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\KlaviyoV3Sdk\Exception\KlaviyoApiException;
use Klaviyo\Reclaim\KlaviyoV3Sdk\Exception\KlaviyoAuthenticationException;
use Klaviyo\Reclaim\KlaviyoV3Sdk\Exception\KlaviyoRateLimitException;

class KlaviyoV3Api
{
    /**
     * Host and versions
     */
    const KLAVIYO_HOST = 'https://a.klaviyo.com/';
    const KLAVIYO_V3_REVISION = '2023-08-15';
    const USER_AGENT = 'Klaviyo/1.0';
    const LIST_V3_API = 'api/list/';
    const EVENT_V3_API = 'client/event/';
    /**
     * Request methods
     */
    const HTTP_POST = 'POST';

    /**
     * Error messages
     */
    const ERROR_INVALID_API_KEY = 'The Private Klaviyo API Key you have set is invalid.';
    const ERROR_EXPIRED_API_KEY = 'The Private Klaviyo API key you have set is no longer valid.';
    const ERROR_UNVERIFIABLE_API_KEY = 'Unable to verify Klaviyo Private API Key.';
    const ERROR_NON_200_STATUS = 'Request Failed with HTTP Status Code: %s';
    const ERROR_RATE_LIMIT_EXCEEDED = 'Rate limit exceeded';
    /**
     * Request options
     */
    const ACCEPT_KEY_HEADER = 'accept';
    const CONTENT_TYPE_KEY_HEADER = 'content-type';
    const REVISION_KEY_HEADER = 'revision';
    const AUTHORIZATION_KEY_HEADER = 'Authorization';
    const KLAVIYO_API_KEY = 'Klaviyo-API-Key';
    const PROPERTIES = 'properties';
    const KLAVIYO_USER_AGENT_KEY = 'X-Klaviyo-User-Agent';
    const APPLICATION_JSON_HEADER_VALUE = 'application/json';

    /**
     * Payload options
     */
    const DATA_KEY_PAYLOAD = 'data';
    const TYPE_KEY_PAYLOAD = 'type';
    const ATTRIBUTE_KEY_PAYLOAD = 'attributes';
    const PROPERTIES_KEY_PAYLOAD = 'properties';
    const TIME_KEY_PAYLOAD = 'time';
    const VALUE_KEY_PAYLOAD = 'value';
    const METRIC_KEY_PAYLOAD = 'metric';
    const PROFILE_KEY_PAYLOAD = 'profile';
    const NAME_KEY_PAYLOAD = 'name';
    const EVENT_VALUE_PAYLOAD = 'event';
    const ID_KEY_PAYLOAD = 'id';
    const PROFILE_SUBSCRIPTION_BULK_CREATE_JOB_PAYLOAD_KEY = 'profile-subscription-bulk-create-job';
    const LIST_PAYLOAD_KEY = 'list';
    const RELATIONSHIPS_PAYLOAD_KEY = 'relationships';
    const PROFILES_PAYLOAD_KEY = 'profiles';
    const CUSTOM_SOURCE_PAYLOAD_KEY = 'custom_source';

    /**
     * @var string
     */
    protected $private_key;

    /**
     * @var string
     */
    protected $public_key;

    /**
     * Constructor method for Base class.
     *
     * @param string $public_key Public key (account ID) for Klaviyo account
     * @param string $private_key Private API key for Klaviyo account
     */
    public function __construct($public_key, $private_key)
    {
        $this->public_key = $public_key;
        $this->private_key = $private_key;
    }

    /**
     * Build headers for the Klaviyo all event
     *
     * @param $clientEvent
     * @return array|array[]
     */
    public function getHeaders($clientEvent)
    {
        $klVersion = $this->_klaviyoScopeSetting->getVersion();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        $m2Version = $productMetadata->getVersion();

        $headers = array(
            CURLOPT_HTTPHEADER => [
                self::REVISION_KEY_HEADER . ': ' . self::KLAVIYO_V3_REVISION,
                self::ACCEPT_KEY_HEADER . ': ' . self::APPLICATION_JSON_HEADER_VALUE,
                self::KLAVIYO_USER_AGENT_KEY . ': ' . 'prestashop-klaviyo/' . $klVersion() . 'Magento2/' . $m2Version . 'PHP/' . phpversion()
            ]
        );

        $headers[CURLOPT_HTTPHEADER][] = $clientEvent ? self::CONTENT_TYPE_KEY_HEADER . ' ' . self::APPLICATION_JSON_HEADER_VALUE : self::AUTHORIZATION_KEY_HEADER . ': ' . self::KLAVIYO_API_KEY . ' ' . $this->private_key;

        return $headers;
    }


    /**
     * Query for all available lists in Klaviyo
     *
     * @return array
     */
    public function getLists()
    {
        $this->sendApiRequest(self::LIST_V3_API, false, 'GET');
    }

    /**
     * Record an event for a customer on their Klaviyo profile
     *  https://developers.klaviyo.com/en/reference/create_client_event
     *
     * @param $config
     * @return array
     */
    public function track($config): array
    {
        $body = array(
            self::DATA_KEY_PAYLOAD => array(
                self::TYPE_KEY_PAYLOAD => self::EVENT_VALUE_PAYLOAD,
                self::ATTRIBUTE_KEY_PAYLOAD =>
                    $this->buildEventProperties($config['properties'], $config['time'], $config['metric']) +
                    $this->buildCustomerProperties($config['customer_properties'])
            )
        );

        return $this->sendApiRequest(self::EVENT_V3_API . '?company_id=' . $this->public_key, true, $body);
    }

    /**
     * Subscribe members to a Klaviyo list
     * https://developers.klaviyo.com/en/reference/create_list_relationships
     *
     * @param $listId
     * @param $profiles
     * @return array
     */
    public function subscribeMembersToList($path, $listId, $profiles)
    {
        $body = array(
            self::DATA_KEY_PAYLOAD => array(
                self::TYPE_KEY_PAYLOAD => self::PROFILE_SUBSCRIPTION_BULK_CREATE_JOB_PAYLOAD_KEY,
                self::ATTRIBUTE_KEY_PAYLOAD => array(
                    self::CUSTOM_SOURCE_PAYLOAD_KEY => 'Magento 2',
                    self::PROFILES_PAYLOAD_KEY => $profiles,
                    self::RELATIONSHIPS_PAYLOAD_KEY => array(
                        self::LIST_PAYLOAD_KEY => array(
                            self::DATA_KEY_PAYLOAD => array(
                                self::TYPE_KEY_PAYLOAD => self::LIST_PAYLOAD_KEY,
                                self::ID_KEY_PAYLOAD => $listId
                            )
                        )
                    )
                )
            )
        );

        return $this->sendApiRequest($path, false, $body);
    }


    /**
     * @param string $email
     * @return array|null|string
     */
    public function unsubscribeEmailFromKlaviyoList($email)
    {
        $path = self::LIST_V3_API . ScopeSetting::API_SUBSCRIBE;
        $fields = [
            'emails' => [(string)$email],
        ];

        return $this->sendApiRequest($path, false, 'POST', $fields);
    }

    /**
     * Request method used by all API methods to make calls
     *
     * @param $path
     * @param $clientEvent
     * @param $method
     * @param $body
     * @return array
     */

    protected function sendApiRequest($path, $clientEvent, $method = null, $body = null)
    {
        $url = self::KLAVIYO_HOST . $path;

        //Add API Key to params
        $params['api_key'] = $this->_klaviyoScopeSetting->getPrivateApiKey();

        $curl = curl_init();
        $options = array(
                CURLOPT_URL => self::KLAVIYO_HOST . $path,
            ) + $this->getHeaders($clientEvent) + $this->getDefaultCurlOptions($method);

        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS][] = $body;
        }

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        $phpVersionHttpCode = version_compare(phpversion(), '5.5.0', '>') ? CURLINFO_RESPONSE_CODE : CURLINFO_HTTP_CODE;
        $statusCode = curl_getinfo($curl, $phpVersionHttpCode);
        curl_close($curl);

        return $this->handleAPIResponse($response, $statusCode, $clientEvent);
    }

    /**
     * Build Event Properties for the Client/Events endpoint
     *
     * @param $eventProperties
     * @param $time
     * @param $metric
     * @return array
     */
    public function buildEventProperties($eventProperties, $time, $metric): array
    {
        $event_time = new DateTime();
        $event_time->setTimestamp($time ?: time());

        return array(
            self::PROPERTIES_KEY_PAYLOAD => $eventProperties,
            self::TIME_KEY_PAYLOAD => $event_time,
            self::VALUE_KEY_PAYLOAD => $eventProperties[self::VALUE_KEY_PAYLOAD],
            self::METRIC_KEY_PAYLOAD => array(
                self::TYPE_KEY_PAYLOAD => self::METRIC_KEY_PAYLOAD,
                self::ATTRIBUTE_KEY_PAYLOAD => array(
                    self::NAME_KEY_PAYLOAD => $metric
                )
            ));
    }

    /**
     * Build customer properties for the Client/Events endpoint
     *
     * @param $customerProperties
     * @return \array[][]
     */
    public function buildCustomerProperties($customerProperties): array
    {
        $kl_properties = array(
            'email' => $customerProperties['$email'],
            'first_name' => $customerProperties['firstname'],
            'last_name' => $customerProperties['lastname']
        );

        unset($customerProperties['email']);
        unset($customerProperties['firstname']);
        unset($customerProperties['lastname']);

        return array(
            self::PROFILE_KEY_PAYLOAD => array(
                self::DATA_KEY_PAYLOAD => array(
                    self::TYPE_KEY_PAYLOAD => self::PROFILE_KEY_PAYLOAD,
                    self::ATTRIBUTE_KEY_PAYLOAD => $kl_properties,
                    self::PROPERTIES => $customerProperties,
                )
            )
        );
    }

    /**
     * Get base options array for curl request.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    protected function getDefaultCurlOptions($method = null)
    {
        return array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => (!empty($method)) ? $method : 'POST',
            CURLOPT_USERAGENT => self::USER_AGENT,
        );
    }

    /**
     * Handle the API response and return the parsed data.
     *
     * @param string $response The raw API response.
     * @param int $statusCode The HTTP status code of the response.
     * @param bool $clientEvent
     * @return array| string |null An array containing the parsed data or null on error.
     * @throws KlaviyoApiException
     * @throws KlaviyoAuthenticationException
     * @throws KlaviyoRateLimitException
     */

    protected function handleAPIResponse($response, $statusCode, $clientEvent = false)
    {
        try {
            $decoded_response = $this->decodeJsonResponse($response);
        } catch (\Exception $error) {
            switch ($statusCode) {
                case 403:
                case 401:
                    throw new KlaviyoAuthenticationException($error['detail'], $statusCode);
                case 429:
                    throw new KlaviyoRateLimitException($error['detail'], $statusCode);
                default:
                    $errorMessage = isset($decoded_response['detail']) ? $decoded_response['detail'] : sprintf(self::ERROR_NON_200_STATUS, $statusCode);
                    throw new KlaviyoApiException($errorMessage, $statusCode);
            }
        }

        if ($clientEvent) {
            return $response;
        }

        return $decoded_response;
    }

    /**
     * Return decoded JSON response as associative or empty array.
     * Certain Klaviyo endpoints (such as Delete) return an empty string on success
     * and so PHP versions >= 7 will throw a JSON_ERROR_SYNTAX when trying to decode it
     *
     * @param string $response
     * @return mixed
     */
    private function decodeJsonResponse($response)
    {
        if (!empty($response)) {
            return json_decode($response, true);
        }
        return json_decode('{}', true);
    }
}
