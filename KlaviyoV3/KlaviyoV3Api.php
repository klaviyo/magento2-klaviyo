<?php

namespace KlaviyoV3Sdk;

use KlaviyoV3Sdk\Exception\KlaviyoAuthenticationException;
use KlaviyoV3Sdk\Exception\KlaviyoRateLimitException;
use KlaviyoV3Sdk\Exception\KlaviyoApiException;
use DateTime;
use KlaviyoPs;

class KlaviyoV3Api
{
    /**
     * Host and versions
     */
    const BASE_URL = 'https://a.klaviyo.com/';
    const KLAVIYO_V3_REVISION = '2023-08-15';
    const USER_AGENT = 'Klaviyo/1.0';
    const KLAVIYO_HOST = 'https://a.klaviyo.com/';
    const LIST_V2_API = 'api/v2/list/';
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
        $klaviyops = new KlaviyoPs();

        $headers = array(
            CURLOPT_HTTPHEADER => [
                self::REVISION_KEY_HEADER . ': ' . self::KLAVIYO_V3_REVISION,
                self::ACCEPT_KEY_HEADER . ': ' . self::APPLICATION_JSON_HEADER_VALUE,
                self::KLAVIYO_USER_AGENT_KEY . ': ' . 'magento2-klaviyo/' . $klaviyops->version . 'Magento2/' . _PS_VERSION_ . 'PHP/' . phpversion()
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
        return $this->make_request('api/lists/', false);
    }

    /**
     * Record an event for a customer on their Klaviyo profile
     *  https://developers.klaviyo.com/en/reference/create_client_event
     *
     * @param $config
     * @return array
     */
    public function track($config)
    {
        $body = array(
            self::DATA_KEY_PAYLOAD => array(
                self::TYPE_KEY_PAYLOAD => self::EVENT_VALUE_PAYLOAD,
                self::ATTRIBUTE_KEY_PAYLOAD =>
                    $this->buildEventProperties($config['properties'], $config['time'], $config['metric']) +
                    $this->buildCustomerProperties($config['customer_properties'])
            )
        );

        return $this->make_request('/client/events/?company_id=' . $this->public_key, true, $body);
    }

    /**
     * Subscribe members to a Klaviyo list
     * https://developers.klaviyo.com/en/reference/create_list_relationships
     *
     * @param $listId
     * @param $profiles
     * @return array
     */
    public function subscribeMembersToList($listId, $profiles)
    {
        $body = array(
            self::DATA_KEY_PAYLOAD => array(
                self::TYPE_KEY_PAYLOAD => self::PROFILE_SUBSCRIPTION_BULK_CREATE_JOB_PAYLOAD_KEY,
                self::ATTRIBUTE_KEY_PAYLOAD => array(
                    self::CUSTOM_SOURCE_PAYLOAD_KEY => 'Prestashop',
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

        return $this->make_request('/api/profile-subscription-bulk-create-jobs/', false, $body);
    }

    /**
     * Request method used by all API methods to make calls
     *
     * @param $path
     * @param $clientEvent
     * @param $body
     * @return array
     */
    protected function make_request($path, $clientEvent, $body = null)
    {
        $curl = curl_init();
        $options = array(
                CURLOPT_URL => self::BASE_URL . $path,
            ) + $this->getHeaders($clientEvent) + $this->getDefaultCurlOptions();

        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS][] = $body;
        }

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        $phpVersionHttpCode = version_compare(phpversion(), '5.5.0', '>') ? CURLINFO_RESPONSE_CODE : CURLINFO_HTTP_CODE;
        $statusCode = curl_getinfo($curl, $phpVersionHttpCode);
        curl_close($curl);

        return $this->handleAPI<?php

namespace KlaviyoV3Sdk;

use KlaviyoV3Sdk\Exception\KlaviyoAuthenticationException;
use KlaviyoV3Sdk\Exception\KlaviyoRateLimitException;
use KlaviyoV3Sdk\Exception\KlaviyoApiException;
use DateTime;
use KlaviyoPs;

class KlaviyoV3Api
{
    /**
     * Host and versions
     */
    const BASE_URL = 'https://a.klaviyo.com/';
    const KLAVIYO_V3_REVISION = '2023-08-15';
    const USER_AGENT = 'Klaviyo/1.0';
    const KLAVIYO_HOST = 'https://a.klaviyo.com/';
    const LIST_V2_API = 'api/v2/list/';
    /**
     * Request methods
     */
    const HTTP_POST = 'POST';

    /**
     * Error messages
     */
    const ERROR_INVALID_API_KEY = 'Invalid API Key.';
    const ERROR_NON_200_STATUS = 'Request Failed with HTTP Status Code: %s';

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
        $klaviyops = new KlaviyoPs();

        $headers = array(
            CURLOPT_HTTPHEADER => [
                self::REVISION_KEY_HEADER . ': ' . self::KLAVIYO_V3_REVISION,
                self::ACCEPT_KEY_HEADER . ': ' . self::APPLICATION_JSON_HEADER_VALUE,
                self::KLAVIYO_USER_AGENT_KEY . ': ' . 'magento2-klaviyo/' . $klaviyops->version . 'Magento2/' . _PS_VERSION_ . 'PHP/' . phpversion()
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
        return $this->make_request('api/lists/', false);
    }

    /**
     * Record an event for a customer on their Klaviyo profile
     *  https://developers.klaviyo.com/en/reference/create_client_event
     *
     * @param $config
     * @return array
     */
    public function track($config)
    {
        $body = array(
            self::DATA_KEY_PAYLOAD => array(
                self::TYPE_KEY_PAYLOAD => self::EVENT_VALUE_PAYLOAD,
                self::ATTRIBUTE_KEY_PAYLOAD =>
                    $this->buildEventProperties($config['properties'], $config['time'], $config['metric']) +
                    $this->buildCustomerProperties($config['customer_properties'])
            )
        );

        return $this->make_request('/client/events/?company_id=' . $this->public_key, true, $body);
    }

    /**
     * Subscribe members to a Klaviyo list
     * https://developers.klaviyo.com/en/reference/create_list_relationships
     *
     * @param $listId
     * @param $profiles
     * @return array
     */
    public function subscribeMembersToList($listId, $profiles)
    {
        $body = array(
            self::DATA_KEY_PAYLOAD => array(
                self::TYPE_KEY_PAYLOAD => self::PROFILE_SUBSCRIPTION_BULK_CREATE_JOB_PAYLOAD_KEY,
                self::ATTRIBUTE_KEY_PAYLOAD => array(
                    self::CUSTOM_SOURCE_PAYLOAD_KEY => 'Prestashop',
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

        return $this->make_request('/api/profile-subscription-bulk-create-jobs/', false, $body);
    }

    /**
     * Request method used by all API methods to make calls
     *
     * @param $path
     * @param $clientEvent
     * @param $body
     * @return array
     */
    protected function make_request($path, $clientEvent, $body = null)
    {
        $url = self::KLAVIYO_HOST . $path;

        $options = array(
            'http' => array(
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => $dataString,
            ),
        );

        $options = array(
                CURLOPT_URL => self::BASE_URL . $path,
            ) + $this->getHeaders($clientEvent) + $this->getDefaultCurlOptions();

        $context  = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        if ($response == '0') {
            $this->_klaviyoLogger->log("Unable to send event to Track API with data: $dataString");
        }

        return $response == '1';
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
    protected function getDefaultCurlOptions()
    {
        return array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => self::HTTP_POST,
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
        $decoded_response = $this->decodeJsonResponse($response);
        if ($statusCode == 403) {
            throw new KlaviyoAuthenticationException(self::ERROR_INVALID_API_KEY, $statusCode);
        }

        if ($statusCode == 401) {
           throw new KlaviyoAuthenticationException(self::ERROR_EXPIRED_API_KEY, $statusCode);
        }

        if ($status_code === 429) {
            throw new KlaviyoAuthenticationException(self::ERROR_UNVERIFIABLE_API_KEY, $statusCode);
        }

        if ($statusCode == 429) {
            throw new KlaviyoRateLimitException(
                $this->returnRateLimit($decoded_response)
            );
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new KlaviyoApiException(isset($decoded_response['detail']) ? $decoded_response['detail'] : sprintf(self::ERROR_NON_200_STATUS, $statusCode), $statusCode);
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
}Response($response, $statusCode, $clientEvent);
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
    protected function getDefaultCurlOptions()
    {
        return array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => self::HTTP_POST,
        );
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