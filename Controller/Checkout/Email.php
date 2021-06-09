<?php

namespace Klaviyo\Reclaim\Controller\Checkout;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Email extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CheckoutSession $checkoutSession
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Endpoint /reclaim/checkout/email resolves here. A quote's email address
     * is AJAX'd here after the email input changes. We look up the current
     * quote and save the email on it, since Magento doesn't do that on its own.
     *
     * @return Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $quote = $this->checkoutSession->getQuote();

        $customerEmail = $this->getRequest()->getParam('email');
        // add email validation
        if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            return $result->setData(['success' => false]);
        }

        $quote->setCustomerEmail($customerEmail);
        $quote->save();

        return $result->setData(['success' => true]);
    }
}
