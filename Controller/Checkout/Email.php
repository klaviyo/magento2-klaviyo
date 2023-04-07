<?php

namespace Klaviyo\Reclaim\Controller\Checkout;

class Email extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_objectManager = $context->getObjectManager();
    }

    /**
     * Endpoint /reclaim/checkout/email resolves here. A quote's email address
     * is AJAX'd here after the email input changes. We look up the current
     * quote and save the email on it, since Magento doesn't do that on its own.
     *
     * @return JSON
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $quote = $this->_objectManager->create('Magento\Checkout\Model\Cart')->getQuote();

        $customer_email = $this->getRequest()->getParam('email');
        // add email validation
        if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $quote->setCustomerEmail($customer_email);
        $quote->save();

        return $result->setData(['success' => $quote->getData()]);
    }
}
