<?php

namespace Klaviyo\Reclaim\Plugin;

use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session;

class CheckoutLayoutPlugin
{
    /**
     * @var ScopeSetting
     */
    private $_klaviyoScopeSetting;

    /**
     * @var Session
     */
    private $_customerSession;

    /**
     * @var CustomerFactory
     */
    private $_customerFactory;

    public function __construct(
        ScopeSetting $klaviyoScopeSetting,
        Session $customerSession,
        CustomerFactory $customerFactory
    ) {
        $this->_klaviyoScopeSetting = $klaviyoScopeSetting;
        $this->_customerSession = $customerSession;
        $this->_customerFactory = $customerFactory;
    }

    /**
     * Checks if logged in user has a default address set, if not returns false.
     *
     * @return Magento\Customer\Model\Address|false
     */
    public function _getDefaultAddressIfSetForCustomer()
    {
        $address = false;
        if ($this->_customerSession->isLoggedIn()) {
            $customerData = $this->_customerSession->getCustomer()->getData();
            $customerId = $customerData["entity_id"];
            $customer = $this->_customerFactory->create()->load($customerId);
            $address = $customer->getDefaultShippingAddress();
        }
        return $address;
    }

    public function afterProcess(\Magento\Checkout\Block\Checkout\LayoutProcessor $processor, $jsLayout)
    {
        if ($this->_klaviyoScopeSetting->getConsentAtCheckoutSMSIsActive()) {
            $smsConsentCheckbox = [
                'component' => 'Magento_Ui/js/form/element/abstract',
                'config' => [
                    'customScope' => 'shippingAddress.custom_attributes',
                    'template' => 'ui/form/field',
                    'elementTmpl' => 'ui/form/element/checkbox',
                    'options' => [],
                    'id' => 'kl_sms_consent',
                ],
                'dataScope' => 'shippingAddress.custom_attributes.kl_sms_consent',
                'label' => $this->_klaviyoScopeSetting->getConsentAtCheckoutSMSConsentLabelText(),
                'description' => $this->_klaviyoScopeSetting->getConsentAtCheckoutSMSConsentText(),
                'provider' => 'checkoutProvider',
                'visible' => true,
                'checked' => false,
                'validation' => [],
                'sortOrder' => $this->_klaviyoScopeSetting->getConsentAtCheckoutSMSConsentSortOrder(),
                'id' => 'kl_sms_consent',
            ];

            $address = $this->_getDefaultAddressIfSetForCustomer();

            if (!$address) {
                $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['kl_sms_consent'] = $smsConsentCheckbox;
            } else {
                // extra un-editable field with saved phone number to display to logged in users with default address set
                $smsConsentTelephone = [
                    'component' => 'Magento_Ui/js/form/element/abstract',
                    'config' =>
                        [
                            'customScope' => 'shippingAddress',
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/input',
                        ],
                    'label' => 'Phone Number',
                    'provider' => 'checkoutProvider',
                    'sortOrder' => '120',
                    'disabled' => true,
                    'visible' => true,
                    'value' => $address->getTelephone()
                ];

                $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['before-form']['children']['kl_sms_phone_number'] = $smsConsentTelephone;
                $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['before-form']['children']['kl_sms_consent'] = $smsConsentCheckbox;
            }
        }

        if (!$this->_customerSession->isLoggedIn() && $this->_klaviyoScopeSetting->getConsentAtCheckoutEmailIsActive()) {
            $emailConsentCheckbox = [
                'component' => 'Magento_Ui/js/form/element/abstract',
                'config' => [
                    'customScope' => 'shippingAddress.custom_attributes',
                    'template' => 'ui/form/field',
                    'elementTmpl' => 'ui/form/element/checkbox',
                    'options' => [],
                    'id' => 'kl_email_consent',
                ],
                'dataScope' => 'shippingAddress.custom_attributes.kl_email_consent',
                'description' => $this->_klaviyoScopeSetting->getConsentAtCheckoutEmailText(),
                'provider' => 'checkoutProvider',
                'visible' => true,
                'checked' => false,
                'validation' => [],
                'sortOrder' => $this->_klaviyoScopeSetting->getConsentAtCheckoutEmailSortOrder(),
                'id' => 'kl_email_consent',
            ];

            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['kl_email_consent'] = $emailConsentCheckbox;
        }


        return $jsLayout;
    }
}
