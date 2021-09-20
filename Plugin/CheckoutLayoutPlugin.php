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

    /**
     * Checks if logged in user has a default address set, if not returns false.
     *
     * @param Magento\Framework\App\ObjectManager $objectManager
     * @param Magento\Customer\Model\Session $customerSession
     *
     * @return Magento\Customer\Model\Address|false
     */
    public function _getDefaultAddressIfSetForCustomer(
        \Magento\Framework\App\ObjectManager $objectManager,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $address = false;
        if ($customerSession->isLoggedIn()) {
            $customerData = $customerSession->getCustomer()->getData();
            $customerFactory = $objectManager->get('\Magento\Customer\Model\CustomerFactory')->create();
            $customerId = $customerData["entity_id"];
            $customer = $customerFactory->load($customerId);
            $address = $customer->getDefaultShippingAddress();
        }
        return $address;
    }

    public function afterProcess(\Magento\Checkout\Block\Checkout\LayoutProcessor $processor, $jsLayout)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');

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

            $address = $this->_getDefaultAddressIfSetForCustomer($objectManager, $customerSession);

            if (!$address)
                $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['kl_sms_consent'] = $smsConsentCheckbox;
            else {

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
