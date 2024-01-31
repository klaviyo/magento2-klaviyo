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

    /**
     * Request methods
     */
    const HTTP_GET = 'GET';
    const HTTP_POST = 'POST';

    /**
     * Error messages
     */
    const ERROR_FORBIDDEN = 'Private API key has invalid permissions.';
    const ERROR_NOT_AUTHORIZED = 'Authentication key missing from request or is invalid. Check that Klaviyo Private API key is correctly set for scope.';
    const ERROR_NON_200_STATUS = 'Request Failed with HTTP Status Code: %s';
    const ERROR_API_CALL_FAILED = 'Request could be completed at this time, API call failed';
    const ERROR_MALFORMED_RESPONSE_BODY = 'Response from API could not be decoded from JSON, check response body';
    const ERROR_RATE_LIMIT_EXCEEDED = 'Rate limit exceeded';
    /**
     * Request options
     */
    const ACCEPT_KEY_HEADER = 'accept';
    const CONTENT_TYPE_KEY_HEADER = 'Content-type';
    const REVISION_KEY_HEADER = 'revision';
    const AUTHORIZATION_KEY_HEADER = 'Authorization';
    const KLAVIYO_API_KEY = 'Klaviyo-API-Key';
    const PROPERTIES = 'properties';
    const KLAVIYO_USER_AGENT_KEY = 'X-Klaviyo-User-Agent';
    const APPLICATION_JSON_HEADER_VALUE = 'application/json';

    /**
     * Payload options
     */
    const CUSTOMER_PROPERTIES_MAP = ['$email' => 'email', 'firstname' => 'first_name', 'lastname' => 'last_name', '$exchange_id' => '_kx'];
    const DATA_KEY_PAYLOAD = 'data';
    const LINKS_KEY_PAYLOAD = 'links';
    const NEXT_KEY_PAYLOAD = 'next';
    const TYPE_KEY_PAYLOAD = 'type';
    const ATTRIBUTE_KEY_PAYLOAD = 'attributes';
    const PROPERTIES_KEY_PAYLOAD = 'properties';
    const TIME_KEY_PAYLOAD = 'time';
    const VALUE_KEY_PAYLOAD = 'value';
    const VALUE_KEY_PAYLOAD_OLD = '$value';
    const METRIC_KEY_PAYLOAD = 'metric';
    const PROFILE_KEY_PAYLOAD = 'profile';
    const NAME_KEY_PAYLOAD = 'name';
    const EVENT_VALUE_PAYLOAD = 'event';
    const ID_KEY_PAYLOAD = 'id';
    const PROFILE_SUBSCRIPTION_BULK_CREATE_JOB_PAYLOAD_KEY = 'profile-subscription-bulk-create-job';
    const PROFILE_SUBSCRIPTION_BULK_DELETE_JOB_PAYLOAD_KEY = 'profile-subscription-bulk-delete-job';
    const LIST_PAYLOAD_KEY = 'list';
    const RELATIONSHIPS_PAYLOAD_KEY = 'relationships';
    const PROFILES_PAYLOAD_KEY = 'profiles';
    const CUSTOM_SOURCE_PAYLOAD_KEY = 'custom_source';
    const MAGENTO_TWO_PAYLOAD_VALUE = 'Magento Two';
    const MAGENTO_TWO_INTEGRATION_SERVICE_KEY = 'magentotwo';
    const SERVICE_PAYLOAD_KEY = 'service';

    /**
     * @var string
     */
    protected $private_key;

    /**
     * @var string
     */
    protected $public_key;
    /**
     * @var ScopeSetting
     */
    private $_klaviyoScopeSetting;

    /**
     * Constructor method for base class
     *
     * @param $public_key
     * @param $private_key
     * @param ScopeSetting $klaviyoScopeSetting
     */
    public function __construct(
        $public_key,
        $private_key,
        ScopeSetting $klaviyoScopeSetting
    ) {
        $this->public_key = $public_key;
        $this->private_key = $private_key;
        $this->_klaviyoScopeSetting = $klaviyoScopeSetting;
    }

    /**
     * Build headers for the Klaviyo all event
     *
     * @param $clientEvent
     * @return array|array[]
     */
    public function getHeaders()
    {
        $klVersion = $this->_klaviyoScopeSetting->getVersion();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        $m2Version = $productMetadata->getVersion();

        $headers = array(
            CURLOPT_HTTPHEADER => [
                self::REVISION_KEY_HEADER . ': ' . self::KLAVIYO_V3_REVISION,
                self::CONTENT_TYPE_KEY_HEADER . ': ' . self::APPLICATION_JSON_HEADER_VALUE,
                self::ACCEPT_KEY_HEADER . ': ' . self::APPLICATION_JSON_HEADER_VALUE,
                self::KLAVIYO_USER_AGENT_KEY . ': ' . 'magento2-klaviyo/' . $klVersion . ' Magento2/' . $m2Version . ' PHP/' . phpversion(),
                self::AUTHORIZATION_KEY_HEADER . ': ' . self::KLAVIYO_API_KEY . ' ' . $this->private_key
            ]
        );

        return $headers;
    }


    /**
     * Query for all available lists in Klaviyo
     * https://developers.klaviyo.com/en/v2023-08-15/reference/get_lists
     *
     * @return array
     * @throws KlaviyoApiException
     * @throws KlaviyoAuthenticationException
     * @throws KlaviyoRateLimitException
     */
    public function getLists()
    {
        $response = $this->requestV3('api/lists/', self::HTTP_GET);
        $lists = $response[self::DATA_KEY_PAYLOAD];

        $next = $response[self::LINKS_KEY_PAYLOAD][self::NEXT_KEY_PAYLOAD];
        while ($next) {
            $next_qs = explode("?", $next)[1];
            $response = $this->requestV3("api/lists/?$next_qs", self::HTTP_GET);
            array_push($lists, ...$response[self::DATA_KEY_PAYLOAD]);

            $next = $response[self::LINKS_KEY_PAYLOAD][self::NEXT_KEY_PAYLOAD];
        }

        return $lists;
    }

    /**
     * Search for profile by Email
     * https://developers.klaviyo.com/en/v2023-08-15/reference/get_profiles
     *
     * @param $email
     * @return false|mixed
     */
    public function searchProfileByEmail($email)
    {
        $response_body = $this->requestV3("api/profiles/?filter=equals(email,'$email')", self::HTTP_GET);

        if (empty($response_body[self::DATA_KEY_PAYLOAD])) {
            return false;
        } else {
            $id = $response_body[self::DATA_KEY_PAYLOAD][0][self::ID_KEY_PAYLOAD];
            return [
                'response' => $response_body,
                'profile_id' => $id
            ];
        }
    }

    /**
     * Add a Profile to a list using profile id
     * https://developers.klaviyo.com/en/v2023-08-15/reference/create_list_relationships
     *
     * @param $list_id
     * @param $profile_id
     * @return array|string|null
     * @throws KlaviyoApiException
     */
    public function addProfileToList($list_id, $profile_id)
    {
        $body = array(
            self::DATA_KEY_PAYLOAD => array(
                array(
                    self::TYPE_KEY_PAYLOAD => self::PROFILE_KEY_PAYLOAD,
                    self::ID_KEY_PAYLOAD => $profile_id
                )
            )
        );

        $this->requestV3("api/lists/$list_id/relationships/profiles/", self::HTTP_POST, $body);
    }

    /**
     * Create a new Profile in Klaviyo
     * https://developers.klaviyo.com/en/v2023-08-15/reference/create_profile
     *
     * @param $profile_properties
     * @return array|string|null
     * @throws KlaviyoApiException
     */
    public function createProfile($profile_properties)
    {
        $body = array(
            self::DATA_KEY_PAYLOAD =>
                array(
                    self::TYPE_KEY_PAYLOAD => self::PROFILE_KEY_PAYLOAD,
                    self::ATTRIBUTE_KEY_PAYLOAD => $profile_properties
                )

        );

        $response_body = $this->requestV3('api/profiles/', self::HTTP_POST, $body);
        $id = $response_body[self::DATA_KEY_PAYLOAD][self::ID_KEY_PAYLOAD];
        return [
            'data' => $response_body,
            'profile_id' => $id
        ];
    }

    /**
     * Record an event for a customer on their Klaviyo profile
     *  https://developers.klaviyo.com/en/reference/create_client_event
     *
     * @param $config
     * @return array
     */
    /**
     * Record an event for a customer on their Klaviyo profile
     * https://developers.klaviyo.com/en/v2023-08-15/reference/create_event
     *
     * @param $config
     * @return array
     * @throws KlaviyoApiException
     * @throws KlaviyoAuthenticationException
     * @throws KlaviyoRateLimitException
     */
    public function track($config)
    {
        $event_time = new DateTime();
        $event_time->setTimestamp($config['time'] ?? time());

        $body = array(
            self::DATA_KEY_PAYLOAD => array(
                self::TYPE_KEY_PAYLOAD => self::EVENT_VALUE_PAYLOAD,
                self::ATTRIBUTE_KEY_PAYLOAD =>
                    $this->buildEventProperties($config['properties'], $event_time->format('Y-m-d\TH:i:s'), $config['event']) +
                    $this->buildCustomerProperties($config['customer_properties'])
            )
        );

        return $this->requestV3('/api/events/', self::HTTP_POST, $body);
    }

    /**
     * Subscribe members to a Klaviyo list
     * https://developers.klaviyo.com/en/reference/subscribe_profiles
     *
     * @param $listId
     * @param $profiles
     * @return array
     * @throws KlaviyoApiException
     * @throws KlaviyoAuthenticationException
     * @throws KlaviyoRateLimitException
     */
    public function subscribeMembersToList($listId, $profiles)
    {
        $body = array(
            self::DATA_KEY_PAYLOAD => array(
                self::TYPE_KEY_PAYLOAD => self::PROFILE_SUBSCRIPTION_BULK_CREATE_JOB_PAYLOAD_KEY,
                self::ATTRIBUTE_KEY_PAYLOAD => array(
                    self::CUSTOM_SOURCE_PAYLOAD_KEY => self::MAGENTO_TWO_PAYLOAD_VALUE,
                    self::PROFILES_PAYLOAD_KEY => array(
                        self::DATA_KEY_PAYLOAD => $profiles
                    )
                ),
                self::RELATIONSHIPS_PAYLOAD_KEY => array(
                    self::LIST_PAYLOAD_KEY => array(
                        self::DATA_KEY_PAYLOAD => array(
                            self::TYPE_KEY_PAYLOAD => self::LIST_PAYLOAD_KEY,
                            self::ID_KEY_PAYLOAD => $listId
                        )
                    )
                )
            )
        );

        return $this->requestV3('/api/profile-subscription-bulk-create-jobs/', self::HTTP_POST, $body);
    }


    /**
     * @param string $email
     * @return array|null|string
     */
    public function unsubscribeEmailFromKlaviyoList($email, $listId)
    {
        $body = array(
            self::DATA_KEY_PAYLOAD => array(
                self::TYPE_KEY_PAYLOAD => self::PROFILE_SUBSCRIPTION_BULK_DELETE_JOB_PAYLOAD_KEY,
                self::ATTRIBUTE_KEY_PAYLOAD => array(
                    self::PROFILES_PAYLOAD_KEY => array(
                        self::DATA_KEY_PAYLOAD => array(
                            array(
                                self::TYPE_KEY_PAYLOAD => self::PROFILE_KEY_PAYLOAD,
                                self::ATTRIBUTE_KEY_PAYLOAD => array(
                                    'email' => $email
                                )
                            )
                        )
                    )
                ),
                self::RELATIONSHIPS_PAYLOAD_KEY => array(
                    self::LIST_PAYLOAD_KEY => array(
                        self::DATA_KEY_PAYLOAD => array(
                            self::TYPE_KEY_PAYLOAD => self::LIST_PAYLOAD_KEY,
                            self::ID_KEY_PAYLOAD => $listId
                        )
                    )
                )
            )
        );

        return $this->requestV3('/api/profile-subscription-bulk-delete-jobs/', self::HTTP_POST, $body);
    }

    /**
     * Request method used by all API methods to make calls
     *
     * @param $path
     * @param $method
     * @param $body
     * @param $attempt
     * @return array|string|null
     * @throws KlaviyoApiException
     * @throws KlaviyoAuthenticationException
     * @throws KlaviyoRateLimitException
     */
    protected function requestV3($path, $method = null, $body = null, $attempt = 0)
    {
        $curl = curl_init();
        $options = array(
                CURLOPT_URL => self::KLAVIYO_HOST . $path,
            ) + $this->getHeaders() + $this->getDefaultCurlOptions($method);

        curl_setopt_array($curl, $options);

        if ($body !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($curl);
        $phpVersionHttpCode = version_compare(phpversion(), '5.5.0', '>') ? CURLINFO_RESPONSE_CODE : CURLINFO_HTTP_CODE;
        $statusCode = curl_getinfo($curl, $phpVersionHttpCode);
        // In the event that the curl_exec fails for whatever reason, it responds with `false`,
        // Implementing a timeout and retry mechanism which will attempt the API call 3 times at 5 second intervals
        if ($statusCode < 200 || $statusCode >= 300 || $response === false) {
            if ($attempt < 3) {
                sleep(1);
                $this->requestV3($path, $method, $body, $attempt + 1);
            } else {
                $this->handleAPIResponse($response, $statusCode);
            }
        }
        curl_close($curl);

        return $this->handleAPIResponse($response, $statusCode);
    }

    /**
     * Build Event Properties for the api/events endpoint
     *
     * @param $eventProperties
     * @param $time
     * @param $metric
     * @return array
     */
    public function buildEventProperties($eventProperties, $time, $metric)
    {
        return array(
            self::PROPERTIES_KEY_PAYLOAD => $eventProperties,
            self::TIME_KEY_PAYLOAD => $time,
            self::VALUE_KEY_PAYLOAD => $eventProperties[self::VALUE_KEY_PAYLOAD_OLD],
            self::METRIC_KEY_PAYLOAD => array(
                self::DATA_KEY_PAYLOAD => array(
                    self::TYPE_KEY_PAYLOAD => self::METRIC_KEY_PAYLOAD,
                    self::ATTRIBUTE_KEY_PAYLOAD => array(
                        self::NAME_KEY_PAYLOAD => $metric,
                        self::SERVICE_PAYLOAD_KEY => self::MAGENTO_TWO_INTEGRATION_SERVICE_KEY
                    )
                )
            )
        );
    }

    /**
     * Build customer properties for the api/events endpoint
     *
     * @param $customerProperties
     * @return array[][]
     */
    public function buildCustomerProperties($customerProperties)
    {
        $kl_properties = [];

        foreach (array_keys(self::CUSTOMER_PROPERTIES_MAP) as $property_name) {
            if (isset($customerProperties[$property_name])) {
                $kl_properties[self::CUSTOMER_PROPERTIES_MAP[$property_name]] = $customerProperties[$property_name];
                unset($customerProperties[$property_name]);
            }
        }

        $data = array(
            self::TYPE_KEY_PAYLOAD => self::PROFILE_KEY_PAYLOAD,
            self::ATTRIBUTE_KEY_PAYLOAD => $kl_properties,
            self::PROPERTIES => $customerProperties,
        );

        if (isset($customerProperties['$id'])) {
            $data[self::ID_KEY_PAYLOAD] = $customerProperties['$id'];
            unset($customerProperties['$id']);
        }

        return array(
            self::PROFILE_KEY_PAYLOAD => array(
                self::DATA_KEY_PAYLOAD => $data
            )
        );
    }

    /**
     * Get base options array for curl request.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    protected function getDefaultCurlOptions($method)
    {
        return array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
        );
    }

    /**
     * Handle the API response and return the parsed data.
     *
     * @param string $response The raw API response.
     * @param int $statusCode The HTTP status code of the response.
     * @return array| string |null An array containing the parsed data or null on error.
     * @throws KlaviyoApiException
     * @throws KlaviyoAuthenticationException
     * @throws KlaviyoRateLimitException
     */
    protected function handleAPIResponse($response, $statusCode)
    {
        $decoded_response = $this->decodeJsonResponse($response);
        if ($statusCode == 401) {
            throw new KlaviyoAuthenticationException(self::ERROR_NOT_AUTHORIZED, $statusCode);
        } elseif ($statusCode == 403) {
            throw new KlaviyoAuthenticationException(self::ERROR_FORBIDDEN, $statusCode);
        } elseif ($statusCode == 429) {
            throw new KlaviyoRateLimitException(
                self::ERROR_RATE_LIMIT_EXCEEDED
            );
        } elseif ($statusCode < 200 || $statusCode >= 300) {
            throw new KlaviyoApiException(isset($decoded_response['errors']) ? $decoded_response['errors'][0]['detail'] : sprintf(self::ERROR_NON_200_STATUS, $statusCode), $statusCode);
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
     * @throws KlaviyoException
     */
    private function decodeJsonResponse($response)
    {
        if (!empty($response)) {
            try {
                return json_decode($response, true);
            } catch (Exception $e) {
                throw new KlaviyoException(self::ERROR_MALFORMED_RESPONSE_BODY);
            }
        }

        return json_decode('{}', true);
    }
}
