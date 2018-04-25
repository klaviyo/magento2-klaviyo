<?php

namespace Klaviyo\Reclaim\Controller\Store;

class MediaBaseUrl extends \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->request = $context->getRequest();
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }
    /**
     * Enpoint /reclaim/store/mediabaseurl resolves here. This endpoint is used indirectly
     * via sections.xml so that the minicart can be updated on the client side on request
     *
     * @return JSON
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        $store_id = $this->request->getParam('store_id');
        $store = $this->_storeManager->getStore($store_id);
        $media_base_url = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

        return $result->setData($media_base_url);
    }
}
