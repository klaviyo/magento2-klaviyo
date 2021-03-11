<?php


namespace Klaviyo\Reclaim\Plugin;

use Klaviyo\Reclaim\Helper\ScopeSetting;


class CheckoutLayoutPlugin
{
    public function __construct(
        ScopeSetting $klaviyoScopeSetting
    ) {
        $this->_klaviyoScopeSetting = $klaviyoScopeSetting;
    }

    public function afterProcess(\Magento\Checkout\Block\Checkout\LayoutProcessor $processor, $jsLayout)
    {
        if ($this->_klaviyoScopeSetting->getConsentAtCheckoutSMSIsActive())
        {
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
                'label' => 'Sign Up for SMS Consent',
                'description' => $this->_klaviyoScopeSetting->getConsentAtCheckoutSMSConsentText(),
                'provider' => 'checkoutProvider',
                'visible' => true,
                'checked' => false,
                'validation' => [],
                'sortOrder' => $this->_klaviyoScopeSetting->getConsentAtCheckoutSMSConsentSortOrder(),
                'id' => 'kl_sms_consent',
            ];

            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['kl_sms_consent'] = $smsConsentCheckbox;
        }
        // Open to ideas here, since we don't overwrite the customer-email section
        // we need to distinguish if the customer is logged in or not, object manager is an easy way to do so
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');

        if (!$customerSession->isLoggedIn() && $this->_klaviyoScopeSetting->getConsentAtCheckoutEmailIsActive())
        {
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
                'label' => 'Sign Up for Email Newsletters',
                'description' => $this->_klaviyoScopeSetting->getConsentAtCheckoutEmailConsentText(),
                'provider' => 'checkoutProvider',
                'visible' => true,
                'checked' => false,
                'validation' => [],
                'sortOrder' => 0,
                'id' => 'kl_email_consent',
            ];

            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['kl_email_consent'] = $emailConsentCheckbox;
        }


        return $jsLayout;
    }
}
