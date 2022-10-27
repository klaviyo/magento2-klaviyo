<?php

namespace Klaviyo\Reclaim\Controller\Checkout;

class Reload extends \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }
    /**
     * Enpoint /reclaim/checkout/reload resolves here. This endpoint is used indirectly
     * via sections.xml so that the minicart can be updated on the client side on request
     *
     * @return JSON
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        return $result->setData(['success' => 1]);
    }
}
