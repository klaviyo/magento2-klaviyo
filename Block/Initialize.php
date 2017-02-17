<?php

namespace Klaviyo\Reclaim\Block;

class Initialize extends \Magento\Framework\View\Element\Template
{
    protected $_helper;
    protected $_objectManager;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Klaviyo\Reclaim\Helper\Data $helper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_helper = $helper;
        $this->_objectManager = $objectManager;
    }

    /**
     * Grab the Klaviyo public API key from the configuration helper and return it.
     * Used to make `identify` calls for `Active on Site` metric (for signed in users)
     * and `track` calls for `Viewed Product` metrics.
     *
     * @return string
     */
    public function getPublicApiKey()
    {
        return $this->_helper->getPublicApiKey();
    }

    /**
     * Grab whether the Klaviyo_Reclaim extension is enabled through Admin from
     * the configuration helper and return it.
     *
     * @return boolean
     */
    public function isKlaviyoEnabled()
    {
        return $this->_helper->getEnabled();
    }

    /**
     * View helper to see if the current user is logged it. Used to know whether
     * we can send an `identify` call for the `Active on Site` metric.
     *
     * @return boolean
     */
    public function isLoggedIn()
    {
        $customerSession = $this->_objectManager->create('Magento\Customer\Model\Session');
        return $customerSession->isLoggedIn();
    }

    /**
     * View helper to get the current users email address. Used to send an
     * `identify` call for the `Active on Site` metric.
     *
     * @return string
     */
    public function getCustomerEmail()
    {
        $customerSession = $this->_objectManager->create('Magento\Customer\Model\Session');
        return $customerSession->getCustomerData()->getEmail();
    }

    /**
     * View helper to get the current users first name. Used to send an
     * `identify` call for the `Active on Site` metric.
     *
     * @return string
     */
    public function getCustomerFirstname()
    {
        $customerSession = $this->_objectManager->create('Magento\Customer\Model\Session');
        return $customerSession->getCustomerData()->getFirstname();
    }

    /**
     * View helper to get the current users last name. Used to send an
     * `identify` call for the `Active on Site` metric.
     *
     * @return string
     */
    public function getCustomerLastname()
    {
        $customerSession = $this->_objectManager->create('Magento\Customer\Model\Session');
        return $customerSession->getCustomerData()->getLastname();
    }
}
