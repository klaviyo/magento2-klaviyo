<?php

namespace Klaviyo\Reclaim\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $_scopeConfig;
    protected $_request;
    protected $_state;

    const ENABLE = 'klaviyo_reclaim_general/general/enable';
    const PUBLIC_API_KEY = 'klaviyo_reclaim_general/general/public_api_key';
    const PRIVATE_API_KEY = 'klaviyo_reclaim_general/general/private_api_key';
    const NEWSLETTER = 'klaviyo_reclaim_newsletter/newsletter/newsletter';

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\State $state,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        parent::__construct($context);
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_request = $context->getRequest();
        $this->_state = $state;
        $this->_storeId = $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getId();
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

    public function getNewsletter()
    {
        return $this->getScopeSetting(self::NEWSLETTER);
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

    public function subscribeEmailToKlaviyoList($email, $first_name=null, $last_name=null) {
        $list_id = $this->getNewsletter();
        $api_key = $this->getPrivateApiKey();

        $properties = [];
        if ($first_name) $properties['$first_name'] = $first_name;
        if ($last_name) $properties['$last_name'] = $last_name;
        $properties_val = count($properties) ? urlencode(json_encode($properties)) : '{}';

        $fields = [
            'api_key=' . $api_key,
            'email=' . urlencode($email),
            'confirm_optin=false',
            'properties=' . $properties_val,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://a.klaviyo.com/api/v1/list/' . $list_id . '/members');
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, join('&', $fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_exec($ch);
        curl_close($ch);
    }

    public function unsubscribeEmailFromKlaviyoList($email) {
        $list_id = $this->getNewsletter();
        $api_key = $this->getPrivateApiKey();

        $fields = [
            'api_key=' . $api_key,
            'email=' . urlencode($email),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://a.klaviyo.com/api/v1/list/' . $list_id . '/members/exclude');
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, join('&', $fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_exec($ch);
        curl_close($ch);
    }
}
