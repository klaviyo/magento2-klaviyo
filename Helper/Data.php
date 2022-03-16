<?php
namespace Klaviyo\Reclaim\Helper;

use \Klaviyo\Reclaim\Helper\ScopeSetting;
use \Magento\Framework\App\Helper\Context;
use \Klaviyo\Reclaim\Helper\Logger;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const USER_AGENT = 'Klaviyo/1.0';
    const KLAVIYO_HOST = 'https://a.klaviyo.com/';
    const LIST_V2_API = 'api/v2/list/';

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

    public function getObserverAtcPayload(){
       return $this->observerAtcPayload;
    }

    public function setObserverAtcPayload($data){
        $this->observerAtcPayload = $data;
    }

    public function unsetObserverAtcPayload(){
        $this->observerAtcPayload = null;
    }

    public function getKlaviyoLists($api_key=null){
        if (!$api_key) $api_key = $this->_klaviyoScopeSetting->getPrivateApiKey();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://a.klaviyo.com/api/v2/lists?api_key=' . $api_key);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);


        $output = json_decode(curl_exec($ch));
        $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($statusCode !== 200) {
            if ($statusCode === 403) {
                $reason = 'The Private Klaviyo API Key you have set is invalid.';
            } elseif ($statusCode === 401) {
                $reason = 'The Private Klaviyo API key you have set is no longer valid.';
            } else {
                $reason = 'Unable to verify Klaviyo Private API Key.';
            }

            $result = [
                'success' => false,
                'reason' => $reason
            ];
        } else {
            usort($output, function($a, $b) {
                return strtolower($a->list_name) > strtolower($b->list_name) ? 1 : -1;
            });

            $result = [
                'success' => true,
                'lists' => $output
            ];
        }

        return $result;
    }

    /**
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @param string $source
     * @return bool|string
     */
    public function subscribeEmailToKlaviyoList($email, $firstName = null, $lastName = null, $source = null)
    {
        $listId = $this->_klaviyoScopeSetting->getNewsletter();
        $optInSetting = $this->_klaviyoScopeSetting->getOptInSetting();

        $properties = [];
        $properties['email'] = $email;
        if ($firstName) $properties['$first_name'] = $firstName;
        if ($lastName) $properties['$last_name'] = $lastName;
        if ($source) $properties['$source'] = $source;
        if ($optInSetting == ScopeSetting::API_SUBSCRIBE) $properties['$consent'] = ['email'];

        $propertiesVal = ['profiles' => $properties];

        $path = self::LIST_V2_API . $listId . $optInSetting;

        try {
            $response = $this->sendApiRequest($path, $propertiesVal, 'POST');
        } catch (\Exception $e) {
            $this->_klaviyoLogger->log(sprintf('Unable to subscribe %s to list %s: %s', $email, $listId, $e));
            $response = false;
        }

        return $response;
    }

    /**
     * @param string $email
     * @return bool|string
     */
    public function unsubscribeEmailFromKlaviyoList($email)
    {
        $listId = $this->_klaviyoScopeSetting->getNewsletter();

        $path = self::LIST_V2_API . $listId . ScopeSetting::API_SUBSCRIBE;
        $fields = [
            'emails' => [(string)$email],
        ];

        try {
            $response = $this->sendApiRequest($path, $fields, 'DELETE');
        } catch (\Exception $e) {
            $this->_klaviyoLogger->log(sprintf('Unable to unsubscribe %s from list %s: %s', $email, $listId, $e));
            $response = false;
        }

        return $response;
    }

    public function klaviyoTrackEvent($event, $customer_properties = [], $properties = [], $timestamp = null, $storeId = null)
    {
        if ((!array_key_exists('$email', $customer_properties) || empty($customer_properties['$email']))
            && (!array_key_exists('$id', $customer_properties) || empty($customer_properties['$id']))
            && (!array_key_exists('$exchange_id', $customer_properties) || empty($customer_properties['$exchange_id'])))
        {

            return 'You must identify a user by email or ID.';
        }
        $params = array(
            'token' => $this->_klaviyoScopeSetting->getPublicApiKey($storeId),
            'event' => $event,
            'properties' => $properties,
            'customer_properties' => $customer_properties
        );

        if (!is_null($timestamp)) {
            $params['time'] = $timestamp;
        }
        return $this->make_request('api/track', $params);

    }

    protected function make_request($path, $params) {
        $url = self::KLAVIYO_HOST . $path;

        $dataString = json_encode($params);
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => $dataString,
            ),
        );

        $context  = stream_context_create($options);
        $response = file_get_contents($url, false,$context);

        if ($response == '0'){
            $this->_klaviyoLogger->log("Unable to send event to Track API with data: $dataString");
        }

        return $response == '1';
    }

    /**
     * @param string $path
     * @param array $params
     * @param string $method
     * @return mixed[]
     * @throws \Exception
     */
    private function sendApiRequest(string $path, array $params, string $method = null)
    {
        $url = self::KLAVIYO_HOST . $path;

        //Add API Key to params
        $params['api_key'] = $this->_klaviyoScopeSetting->getPrivateApiKey();

        $curl = curl_init();
        $encodedParams = json_encode($params);

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => (!empty($method)) ? $method : 'POST',
            CURLOPT_POSTFIELDS => $encodedParams,
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($encodedParams)
            ],
        ]);

        // Submit the request
        $response = curl_exec($curl);
        $err = curl_errno($curl);

        if ($err) {
            throw new \Exception(curl_error($curl));
        }

        // Close cURL session handle
        curl_close($curl);

        return $response;
    }
}
