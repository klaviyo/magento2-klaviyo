<?php

namespace Klaviyo\Reclaim\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Reload extends Action
{
    private $resultJsonFactory;

    public function __construct(Context $context, JsonFactory $resultJsonFactory)
    {
        parent::__construct($context);

        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Enpoint /reclaim/checkout/reload resolves here. This endpoint is used indirectly
     * via sections.xml so that the minicart can be updated on the client side on request
     *
     * @return Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        return $result->setData(['success' => 1]);
    }
}
