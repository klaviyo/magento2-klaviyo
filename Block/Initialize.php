<?php

namespace Klaviyo\Reclaim\Block;

class Initialize extends \Magento\Framework\View\Element\Template
{
    protected $klaviyoScopeSetting;
    protected $session;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Klaviyo\Reclaim\Helper\ScopeSetting $klaviyoScopeSetting,
        \Magento\Customer\Model\Session $session,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->klaviyoScopeSetting = $klaviyoScopeSetting;
        $this->session = $session;
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
        return $this->klaviyoScopeSetting->getPublicApiKey();
    }

    /**
     * Grab whether the Klaviyo_Reclaim extension is enabled through Admin from
     * the configuration helper and return it.
     *
     * @return boolean
     */
    public function isKlaviyoEnabled()
    {
        return $this->klaviyoScopeSetting->isEnabled();
    }

    /**
     * View helper to see if the current user is logged it. Used to know whether
     * we can send an `identify` call for the `Active on Site` metric.
     *
     * @return boolean
     */
    public function isLoggedIn()
    {
        return $this->session->isLoggedIn();
    }

    /**
     * View helper to get the current users email address. Used to send an
     * `identify` call for the `Active on Site` metric.
     *
     * @return string
     */
    public function getCustomerEmail()
    {
        return $this->session->getCustomerData()->getEmail();
    }

    /**
     * View helper to get the current users first name. Used to send an
     * `identify` call for the `Active on Site` metric.
     *
     * @return string
     */
    public function getCustomerFirstname()
    {
        return $this->session->getCustomerData()->getFirstname();
    }

    /**
     * View helper to get the current users last name. Used to send an
     * `identify` call for the `Active on Site` metric.
     *
     * @return string
     */
    public function getCustomerLastname()
    {
        return $this->session->getCustomerData()->getLastname();
    }
}
