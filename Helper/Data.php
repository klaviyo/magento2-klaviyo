<?php
namespace Klaviyo\Reclaim\Helper;

use Klaviyo\Reclaim\KlaviyoV3Sdk\KlaviyoV3Api;
use \Klaviyo\Reclaim\Helper\ScopeSetting;
use \Magento\Framework\App\Helper\Context;
use \Klaviyo\Reclaim\Helper\Logger;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
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

    public function __construct(
        Context $context,
        Logger $klaviyoLogger,
        ScopeSetting $klaviyoScopeSetting
    ) {
        parent::__construct($context);
        $this->_klaviyoLogger = $klaviyoLogger;
        $this->_klaviyoScopeSetting = $klaviyoScopeSetting;
    }

    public function getKlaviyoLists($api_key=null){
        if (!$api_key) {
            $api_key = $this->_klaviyoScopeSetting->getPrivateApiKey();
        }
        $api = new KlaviyoV3Api($this->_klaviyoScopeSetting->getPublicApiKey(), $api_key, $this->_klaviyoScopeSetting, $this->_klaviyoLogger);
        $lists = array();
        $success = true;
        $error = null;
        try {
            $lists_response = $api->getLists();
            foreach ($lists_response as $list) {
                $lists[] = array(
                    'id' => $list['id'],
                    'name' => $list['attributes']['name']
                );
            }
        }  catch (\Exception $e) {
            $this->_klaviyoLogger->log(sprintf('Unable to fetch lists: %s', $e->getMessage()));
            $error = $e->getCode();
            $success = false;
        }

        return [
            'success' => $success,
            'lists' => $lists,
            'error' => $error
        ];
    }

    /**
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @param string $source
     * @return bool|string
     */
    public function subscribeEmailToKlaviyoList($email)
    {
        $api = new KlaviyoV3Api($this->_klaviyoScopeSetting->getPublicApiKey(), $this->_klaviyoScopeSetting->getPrivateApiKey(), $this->_klaviyoScopeSetting, $this->_klaviyoLogger);
        $listId = $this->_klaviyoScopeSetting->getNewsletter();
        $optInSetting = $this->_klaviyoScopeSetting->getOptInSetting();

        $profileAttributes = [];
        $profileAttributes['email'] = $email;

        try {
            if ($optInSetting == ScopeSetting::API_SUBSCRIBE) {
                // Subscribe profile using the profile creation endpoint for lists
                $consent_profile_object = array(
                    'type' => 'profile',
                    'attributes' => array_merge($profileAttributes, array('subscriptions' => array(
                        'email' => [
                            'MARKETING'
                        ]
                    )))
                );
                $api->subscribeMembersToList($listId, array($consent_profile_object));
            } else {
                // Search for profile by email using the api/profiles endpoint
                $existing_profile = $api->searchProfileByEmail($email);
                if (!$existing_profile) {
                    $new_profile = $api->createProfile($profileAttributes);
                    $profile_id = $new_profile["profile_id"];
                } else {
                    $profile_id = $existing_profile["profile_id"];
                }
                $api->addProfileToList($listId, $profile_id);
            }
        } catch (\Exception $e) {
            $this->_klaviyoLogger->log(sprintf('Unable to subscribe %s to list %s: %s', $email, $listId, $e->getMessage()));
        }
    }

    /**
     * @param string $email
     * @return bool|string
     */
    public function unsubscribeEmailFromKlaviyoList($email)
    {
        $api = new KlaviyoV3Api($this->_klaviyoScopeSetting->getPublicApiKey(), $this->_klaviyoScopeSetting->getPrivateApiKey(), $this->_klaviyoScopeSetting, $this->_klaviyoLogger);
        $listId = $this->_klaviyoScopeSetting->getNewsletter();

        try {
            $response = $api->unsubscribeEmailFromKlaviyoList($email, $listId);
        } catch (\Exception $e) {
            $this->_klaviyoLogger->log(sprintf('Unable to unsubscribe %s from list %s: %s', $email, $listId, $e->getMessage()));
            $response = false;
        }

        return $response;
    }
}