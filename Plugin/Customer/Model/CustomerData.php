<?php

namespace Klaviyo\Reclaim\Plugin\Customer\Model;

use Magento\Customer\CustomerData\Customer;
use Magento\Customer\Helper\Session\CurrentCustomer;

/**
 * Class CustomerData
 * @package Klaviyo\Reclaim\Plugin\Customer\Model
 */
class CustomerData
{
    /**
     * @var CurrentCustomer
     */
    private $currentCustomer;

    /**
     * CustomerData constructor.
     * @param CurrentCustomer $currentCustomer
     */
    public function __construct(
        CurrentCustomer $currentCustomer
    ) {
        $this->currentCustomer = $currentCustomer;
    }

    /**
     * @param Customer $subject
     * @param $result
     * @return mixed
     */
    public function afterGetSectionData(Customer $subject, $result)
    {
        $result['lastname'] = '';
        $result['email'] = '';
        if ($this->currentCustomer->getCustomerId()) {
            $result['lastname'] = $this->currentCustomer->getCustomer()->getLastname();
            $result['email'] = $this->currentCustomer->getCustomer()->getEmail();
        }
        return $result;
    }
}
