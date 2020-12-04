<?php
namespace Klaviyo\Reclaim\Helper;

class ScopeSetting extends \Magento\Framework\App\Helper\AbstractHelper
{
    const MODULE_NAME = 'Klaviyo_Reclaim';
    const ENABLE = 'klaviyo_reclaim_general/general/enable';
    const PUBLIC_API_KEY = 'klaviyo_reclaim_general/general/public_api_key';
    const PRIVATE_API_KEY = 'klaviyo_reclaim_general/general/private_api_key';
    const CUSTOM_MEDIA_URL = 'klaviyo_reclaim_general/general/custom_media_url';
    const USING_KLAVIYO_LOGGER = 'klaviyo_reclaim_general/general/logger';

    const NEWSLETTER = 'klaviyo_reclaim_newsletter/newsletter/newsletter';
    const USING_KLAVIYO_LIST_OPT_IN = 'klaviyo_reclaim_newsletter/newsletter/using_klaviyo_list_opt_in';
    const API_MEMBERS = '/members';
    const API_SUBSCRIBE = '/subscribe';

    const CONSENT_AT_CHECKOUT_EMAIL_IS_ACTIVE = 'klaviyo_reclaim_consent_at_checkout/email_consent/is_active';
    const CONSENT_AT_CHECKOUT_EMAIL_LIST_ID = 'klaviyo_reclaim_consent_at_checkout/email_consent/list_id';
    const CONSENT_AT_CHECKOUT_EMAIL_CONSENT_TEXT = 'klaviyo_reclaim_consent_at_checkout/email_consent/consent_text';
    const CONSENT_AT_CHECKOUT_EMAIL_SORT_ORDER = 'klaviyo_reclaim_consent_at_checkout/email_consent/sort_order';

    const CONSENT_AT_CHECKOUT_SMS_IS_ACTIVE = 'klaviyo_reclaim_consent_at_checkout/sms_consent/is_active';
    const CONSENT_AT_CHECKOUT_SMS_LIST_ID = 'klaviyo_reclaim_consent_at_checkout/sms_consent/list_id';
    const CONSENT_AT_CHECKOUT_SMS_CONSENT_TEXT = 'klaviyo_reclaim_consent_at_checkout/sms_consent/consent_text';
    const CONSENT_AT_CHECKOUT_SMS_SORT_ORDER = 'klaviyo_reclaim_consent_at_checkout/sms_consent/sort_order';
    const CONSENT_AT_CHECKOUT_SMS_LABEL_TEXT = 'klaviyo_reclaim_consent_at_checkout/sms_consent/label_text';

    const KLAVIYO_NAME_DEFAULT = 'klaviyo';
    const KLAVIYO_USERNAME = 'klaviyo_reclaim_user/klaviyo_user/username';
    const KLAVIYO_PASSWORD = 'klaviyo_reclaim_user/klaviyo_user/password';
    const KLAVIYO_EMAIL = 'klaviyo_reclaim_user/klaviyo_user/email';


    const WEBHOOK_SECRET = 'klaviyo_reclaim_webhook/klaviyo_webhooks/webhook_secret';
    const PRODUCT_DELETE_BEFORE = 'klaviyo_reclaim_webhook/klaviyo_webhooks/using_product_delete_before_webhook';
    
    const KLAVIYO_OAUTH_NAME = 'klaviyo_reclaim_oauth/klaviyo_oauth/integration_name';

    protected $_scopeConfig;
    protected $_request;
    protected $_state;
    protected $_moduleList;
    protected $_configWriter;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\State $state,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
    ) {
        parent::__construct($context);
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_request = $context->getRequest();
        $this->_state = $state;
        $this->_storeId = $storeManager->getStore()->getId();
        $this->_moduleList = $moduleList;
        $this->_configWriter = $configWriter;
    }

    /**
     * helper function to allow this class to be used in Setup files
     */
    protected function checkAreaCode()
    {
        /**
         * when this class is accessed from cli commands, there is no area code set
         * (since there is no actual session running persay)
         * this try-catch block is needed to allow this helper to be used in setup files
         */
        try{
            $this->_state->getAreaCode();
        }
        catch (\Magento\Framework\Exception\LocalizedException $ex) {
            $this->_state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        }
    }

    /**
     * Getter method for a given scope setting
     * @param string $path
     * @param int $storeId
     * @return
     */
    protected function getScopeSetting($path, $storeId = null)
    {
        $this->checkAreaCode();

        if (isset($storeId)) {
            $scopedStoreCode = $storeId;
        } elseif ($this->_state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
            $scopedStoreCode = $this->_request->getParam('store');
            $scopedWebsiteCode = $this->_request->getParam('website');
        } else {
            // In frontend area. Only concerned with store for frontend.
            $scopedStoreCode = $this->_storeId;
        }

        if (isset($scopedStoreCode)) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            return $this->_scopeConfig->getValue($path, $scope, $scopedStoreCode);
        } elseif (isset($scopedWebsiteCode)) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
            return $this->_scopeConfig->getValue($path, $scope, $scopedWebsiteCode);
        } else {
            return $this->_scopeConfig->getValue($path);
        };
    }

    /**
     * Setter method for a given scope setting
     * @param string $path
     * @param mixed $value
     */
    protected function setScopeSetting($path, $value)
    {
        $this->checkAreaCode();

        if ($this->_state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
            $scopedStoreCode = $this->_request->getParam('store');
            $scopedWebsiteCode = $this->_request->getParam('website');
        } else {
            // In frontend area. Only concerned with store for frontend.
            $scopedStoreCode = $this->_storeId;
        }

        if (isset($scopedStoreCode)) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            return $this->_configWriter->save($path, $value, $scope, $scopedStoreCode);
        } elseif (isset($scopedWebsiteCode)) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
            return $this->_configWriter->save($path, $value, $scope, $scopedWebsiteCode);
        } else {
            return $this->_configWriter->save($path, $value);
        };
    }

    public function getVersion()
    {
        return $this->_moduleList
            ->getOne(self::MODULE_NAME)['setup_version'];
    }

    public function getWebhookSecret()
    {
        return $this->getScopeSetting(self::WEBHOOK_SECRET);
    }

    public function isEnabled()
    {
        return $this->getScopeSetting(self::ENABLE);
    }

    public function getPublicApiKey($storeId = null)
    {
        return $this->getScopeSetting(self::PUBLIC_API_KEY, $storeId);
    }

    public function getPrivateApiKey()
    {
        return $this->getScopeSetting(self::PRIVATE_API_KEY);
    }

    public function setPrivateApiKey($value)
    {
        return $this->setScopeSetting(self::PRIVATE_API_KEY, $value);
    }

    public function isLoggerEnabled()
    {
        return $this->getScopeSetting(self::USING_KLAVIYO_LOGGER);
    }

    public function getKlaviyoUsername()
    {
        return $this->getScopeSetting(self::KLAVIYO_USERNAME);
    }

    public function unsetKlaviyoUsername()
    {
        return $this->setScopeSetting(self::KLAVIYO_USERNAME, self::KLAVIYO_NAME_DEFAULT);
    }

    public function getKlaviyoPassword()
    {
        return $this->getScopeSetting(self::KLAVIYO_PASSWORD);
    }

    public function unsetKlaviyoPassword()
    {
        return $this->setScopeSetting(self::KLAVIYO_PASSWORD, '');
    }

    public function getKlaviyoEmail()
    {
        return $this->getScopeSetting(self::KLAVIYO_EMAIL);
    }

    public function unsetKlaviyoEmail()
    {
        return $this->setScopeSetting(self::KLAVIYO_EMAIL, '');
    }

    public function getKlaviyoOauthName()
    {
        return $this->getScopeSetting(self::KLAVIYO_OAUTH_NAME);
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


    public function getConsentAtCheckoutEmailIsActive()
    {
        return $this->getScopeSetting(self::CONSENT_AT_CHECKOUT_EMAIL_IS_ACTIVE);
    }
    public function getConsentAtCheckoutEmailListId()
    {
        return $this->getScopeSetting(self::CONSENT_AT_CHECKOUT_EMAIL_LIST_ID);
    }
    public function getConsentAtCheckoutEmailText()
    {
        return $this->getScopeSetting(self::CONSENT_AT_CHECKOUT_EMAIL_CONSENT_TEXT);
    }
    public function getConsentAtCheckoutEmailSortOrder()
    {
        return $this->getScopeSetting(self::CONSENT_AT_CHECKOUT_EMAIL_SORT_ORDER);
    }


    public function getConsentAtCheckoutSMSIsActive()
    {
        return $this->getScopeSetting(self::CONSENT_AT_CHECKOUT_SMS_IS_ACTIVE);
    }
    public function getConsentAtCheckoutSMSListId()
    {
        return $this->getScopeSetting(self::CONSENT_AT_CHECKOUT_SMS_LIST_ID);
    }
    public function getConsentAtCheckoutSMSConsentText()
    {
        return $this->getScopeSetting(self::CONSENT_AT_CHECKOUT_SMS_CONSENT_TEXT);
    }
    public function getConsentAtCheckoutSMSConsentSortOrder()
    {
        return $this->getScopeSetting(self::CONSENT_AT_CHECKOUT_SMS_SORT_ORDER);
    }
    public function getConsentAtCheckoutSMSConsentLabelText()
    {
        return $this->getScopeSetting(self::CONSENT_AT_CHECKOUT_SMS_LABEL_TEXT);
    }


    /**
     * This maps a klaviyo account to all the store ids it's scoped to.
     * @param $storeIds
     * @return array
     */
    public function getStoreIdKlaviyoAccountSetMap($storeIds)
    {

        $storeMap = array();
        foreach ($storeIds as $storeId) {
            $klaviyoAccount = $this->getPublicApiKey($storeId);
            if (!array_key_exists($klaviyoAccount, $storeMap)) {
                $storeMap[$klaviyoAccount] = array($storeId);
            } else {
                array_push( $storeMap[$klaviyoAccount], $storeId);
            }
        }

        return $storeMap;
    }

    public function getProductDeleteBeforeSetting($storeId = null)
    {
        return $this->getScopeSetting(self::PRODUCT_DELETE_BEFORE, $storeId);
    }

}

