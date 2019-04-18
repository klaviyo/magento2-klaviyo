<?php
namespace Klaviyo\Reclaim\Model;
use Klaviyo\Reclaim\Api\ReclaimInterface;
use \Magento\Framework\Exception\NotFoundException;


class Reclaim implements ReclaimInterface
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;
    public $response;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager, 
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Klaviyo\Reclaim\Helper\Data $klaviyoHelper
        )
    {
        $this->quoteFactory = $quoteFactory;
        $this->_objectManager = $objectManager;
        $this->_klaviyoHelper = $klaviyoHelper;

    }

    /**
     * Returns extension version
     *
     * @api
     * @return string
     */
    public function reclaim(){
        return $this->_klaviyoHelper->getVersion();
    }

    /**
     * Returns all stores with extended descriptions
     *
     * @api
     * @return mixed
     */
    public function stores()
    {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $store_manager = $object_manager->get('\Magento\Store\Model\StoreManagerInterface');
        $stores = $store_manager->getStores();

        $hydrated_stores = array();
        foreach ($stores as $store)
        {
            $store_id = $store->getId();
            $store_website_id = $store->getWebsiteId();
            $store_name = $store->getName();
            $store_code = $store->getCode();
            $base_url = $store->getBaseUrl();
            $media_base_url = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

            array_push($hydrated_stores, array(
              'id' => $store_id,
              'website_id' => $store_website_id,
              'name' => $store_name,
              'code' => $store_code,
              'base_url' => $base_url,
              'media_base_url' => $media_base_url,
            ));
        }

        return $hydrated_stores;
    }
    public function product($filter) {
        $quote_id = $filter['1'];
        $item_id = $filter['2'];

        $quote = $this->quoteFactory->create()->load($quote_id);
        if (!$quote){
            throw new NotFoundException(__('quote not found'));
        }

        $item = $quote->getItemById($item_id);
        if (!$item){
            throw new NotFoundException(__('item not found'));
        }

        $product = $this->_objectManager->get('Magento\Catalog\Model\Product')->load($item->getProductId());

        $image_array = $this->_getImages($product);
        
        $response = array(
            'id' => $item->getProductId(),
            'images' => $image_array
        );

        return $response;
    }

    // handle inspector tasks to return products by id
    public function productinspector($filter){
        $start = $filter['1'];
        $end = $filter['2'];

        if (($end - $start) > 100){
            throw new NotFoundException(__('100 is the max batch'));
        } elseif (!$start || !$end) {
            throw new NotFoundException(__('provide a start and end filter'));
        }

        $response = array();
        foreach (range($start, $end) as $number) {
            $product = $this->_objectManager
                ->create('Magento\Catalog\Model\Product')
                ->load($number);

            if (!$product){
                continue;
            }
            $response[] = array(
                'id' => $product->getId(),
                'sku' => $product->getSku(),
                'type_id' => $product->getTypeId(),
                'price' => $product->getPrice()
            );
        }

        return $response;

    }
    public function _getImages($product){
        $images = $product->getMediaGalleryImages();
        $image_array = array();
        foreach($images as $image) {
            $image_url = $image->getUrl();
            if ($image_url){
                $image_array[] = $image_url;
            }
        }
        return $image_array;
    }
}
