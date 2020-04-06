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

    const KLAVIYO_NAME_DEFAULT = 'klaviyo';
    const KLAVIYO_USERNAME = 'klaviyo_reclaim_user/klaviyo_user/username';
    const KLAVIYO_PASSWORD = 'klaviyo_reclaim_user/klaviyo_user/password';
    const KLAVIYO_EMAIL = 'klaviyo_reclaim_user/klaviyo_user/email';

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
     */
    protected function getScopeSetting($path)
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

    public function isEnabled()
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
}