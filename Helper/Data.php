<?php

namespace Klaviyo\Reclaim\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const MODULE_NAME = 'Klaviyo_Reclaim';
    const USER_AGENT = 'Klaviyo/1.0';
    const KLAVIYO_HOST =  'https://a.klaviyo.com/';

    protected $_scopeConfig;
    protected $_request;
    protected $_state;
    protected $_moduleList;

    const ENABLE = 'klaviyo_reclaim_general/general/enable';
    const PUBLIC_API_KEY = 'klaviyo_reclaim_general/general/public_api_key';
    const PRIVATE_API_KEY = 'klaviyo_reclaim_general/general/private_api_key';
    const CUSTOM_MEDIA_URL = 'klaviyo_reclaim_general/general/custom_media_url';
    const NEWSLETTER = 'klaviyo_reclaim_newsletter/newsletter/newsletter';
    const USING_KLAVIYO_LIST_OPT_IN = 'klaviyo_reclaim_newsletter/newsletter/using_klaviyo_list_opt_in';

    const API_MEMBERS = '/members';
    const API_SUBSCRIBE = '/subscribe';

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\State $state,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Module\ModuleListInterface $moduleList
    ) {
        parent::__construct($context);
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_request = $context->getRequest();
        $this->_state = $state;
        $this->_storeId = $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getId();
        $this->_moduleList = $moduleList;
    }

    protected function getScopeSetting($path){

        if ($this->_state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
            $storeId = $this->_request->getParam('store');
            $websiteId = $this->_request->getParam('website');
        } else {
            // In frontend area. Only concerned with store for frontend.
            $storeId = $this->_storeId;
        }

        if (isset($storeId)) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $value = $storeId;
            return $this->_scopeConfig->getValue($path, $scope, $value);
        } elseif (isset($websiteId)) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
            $value = $websiteId;
            return $this->_scopeConfig->getValue($path, $scope, $value);
        } else {
            return $this->_scopeConfig->getValue($path);
        };
    }

    public function getVersion()
    {
        return $this->_moduleList
            ->getOne(self::MODULE_NAME)['setup_version'];
    }

    public function getEnabled()
    {
        return $this->getScopeSetting(self::ENABLE);
    }

    public function getPublicApiKey()
    {
        return $this->getScopeSetting(self::PUBLIC_API_KEY);
    }

    public function getPrivateApiKey()
    {
        return $this->getScopeSetting(self::PRIVATE_API_KEY);
    }

    public function getCustomMediaURL()
    {
        return $this->getScopeSetting(self::CUSTOM_MEDIA_URL);
    }

    public function getNewsletter()
    {
        return $this->getScopeSetting(self::NEWSLETTER);
    }

    public function getOptInSetting()
    {
        if ($this->getScopeSetting(self::USING_KLAVIYO_LIST_OPT_IN)) {
            return self::API_SUBSCRIBE;
        } else {
            return self::API_MEMBERS;
        }
    }

    public function getKlaviyoLists($api_key=null){
        if (!$api_key) $api_key = $this->getPrivateApiKey();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://a.klaviyo.com/api/v1/lists?api_key=' . $api_key);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $output = json_decode(curl_exec($ch));
        curl_close($ch);

        if (property_exists($output, 'status')) {
            $status = $output->status;
            if ($status === 403) {
                $reason = 'The Private Klaviyo API Key you have set is invalid.';
            } elseif ($status === 401) {
                $reason = 'The Private Klaviyo API key you have set is no longer valid.';
            } else {
                $reason = 'Unable to verify Klaviyo Private API Key.';
            }

            $result = [
                'success' => false,
                'reason' => $reason
            ];
        } else {
            $static_groups = array_filter($output->data, function($list) {
                return $list->list_type === 'list';
            });

            usort($static_groups, function($a, $b) {
                return strtolower($a->name) > strtolower($b->name) ? 1 : -1;
            });

            $result = [
                'success' => true,
                'lists' => $static_groups
            ];
        }

        return $result;
    }

    public function subscribeEmailToKlaviyoList($email, $first_name=null, $last_name=null, $source=null) {
        $list_id = $this->getNewsletter();
        $opt_in = $this->getOptInSetting();
        $api_key = $this->getPrivateApiKey();

        $properties = [];
        $properties['email'] = $email;
        if ($first_name) $properties['$first_name'] = $first_name;
        if ($last_name) $properties['$last_name'] = $last_name;
        if ($source) $properties['$source'] = $source;

        $properties_val = count($properties) ? json_encode(array('profiles' => $properties)) : '{}';

        $url = "https://a.klaviyo.com/api/v2/list/" . $list_id . $opt_in . "?api_key=" . $api_key;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $properties_val,
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_HTTPHEADER => array(
              "Content-Type: application/json",
              'Content-Length: ' . strlen($properties_val)
            ),
        ));
        // Submit the POST request
        $response = curl_exec($curl);
        $err = curl_error($curl);
        // Close cURL session handle
        curl_close($curl);

        return $response;
    }

    public function unsubscribeEmailFromKlaviyoList($email)
    {
        $list_id = $this->getNewsletter();
        $api_key = $this->getPrivateApiKey();

        $url = 'https://a.klaviyo.com/api/v2/list/' . $list_id . '/subscribe';
        $fields = json_encode([
            'api_key' => (string)$api_key,
            'emails' => [(string)$email],
        ]);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($fields)
            ],
        ]);
        // Submit the POST request
        $response = curl_exec($curl);
        $err = curl_error($curl);
        // Close cURL session handle
        curl_close($curl);

        return $response;
    }

    public function klaviyoTrackEvent($event, $customer_properties=array(), $properties=array(), $timestamp=NULL)
    {
        if ((!array_key_exists('$email', $customer_properties) || empty($customer_properties['$email']))
            && (!array_key_exists('$id', $customer_properties) || empty($customer_properties['$id']))) {

            return 'You must identify a user by email or ID.';
        }
        $params = array(
            'token' => $this->getPublicApiKey(),
            'event' => $event,
            'properties' => $properties,
            'customer_properties' => $customer_properties
        );

        if (!is_null($timestamp)) {
            $params['time'] = $timestamp;
        }
        $encoded_params = $this->build_params($params);
        return $this->make_request('api/track', $encoded_params);

    }
    protected function build_params($params) {
        return 'data=' . urlencode(base64_encode(json_encode($params)));
    }

    protected function make_request($path, $params) {
        $url = self::KLAVIYO_HOST . $path . '?' . $params;
        $response = file_get_contents($url);
        return $response == '1';
    }
}
