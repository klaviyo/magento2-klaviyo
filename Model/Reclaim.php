<?php

namespace Klaviyo\Reclaim\Model;

use Klaviyo\Reclaim\Api\ReclaimInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\ObjectManagerInterface;

class Reclaim implements ReclaimInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * @var \Magento\CatalogInventory\Api\StockStateInterface
     */
    protected $_stockItem;

    /**
     * @var \Magento\CatalogInventory\Model\Stock\StockItemRepository
     */
    protected $_stockItemRepository;

    /**
     * @var \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory
     */
    protected $_subscriberCollection;

    /**
     * @var \Klaviyo\Reclaim\Helper\Logger
     */
    protected $_klaviyoLogger;

    /**
     * @var \Klaviyo\Reclaim\Helper\ScopeSetting
     */
    protected $_klaviyoScopeSetting;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    const MAX_QUERY_DAYS = 10;
    const SUBSCRIBER_BATCH_SIZE = 500;
    public function __construct(
        ObjectManagerInterface $objectManager,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\CatalogInventory\Api\StockStateInterface $stockItem,
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollection,
        \Klaviyo\Reclaim\Helper\ScopeSetting $klaviyoScopeSetting,
        \Klaviyo\Reclaim\Helper\Logger $klaviyoLogger
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->_productFactory = $productFactory;
        $this->_objectManager = $objectManager;
        $this->_stockItem = $stockItem;
        $this->_stockItemRepository = $stockItemRepository;
        $this->_subscriberCollection = $subscriberCollection;
        $this->_klaviyoLogger = $klaviyoLogger;
        $this->_klaviyoScopeSetting = $klaviyoScopeSetting;
    }

    /**
     * Returns extension version
     *
     * @api
     * @return string
     */
    public function reclaim()
    {
        return $this->_klaviyoScopeSetting->getVersion();
    }

    public function getWebhookSecret()
    {
        return $this->_klaviyoScopeSetting->getWebhookSecret();
    }

    /**
     * Returns the Klaviyo log file
     *
     * @api
     * @return string
     */
    public function getLog()
    {
        try {
            $log = file($this->_klaviyoLogger->getPath());
        } catch (\Exception $e) {
            return array (
                'message' => 'Unable to retrieve log file with error: ' . $e->getMessage()
            );
        }

        if (!empty($log)) {
            return $log;
        } else {
            return array (
                'message' => 'Log file is empty'
            );
        }
    }

    /**
     * Cleans the Klaviyo log file
     *
     * @api
     * @param string $date
     * @return boolean
     */
    public function cleanLog($date)
    {
        //attempt to parse unix timestamp from api request parameter
        $cursor = strtotime($date);

        //check if we were able to parse the timestamp
        //if no timestamp, return failure message
        if ($cursor == '') {
            $response = array(
                'message' => 'Unable to parse timestamp: ' . $date
            );
            $this->_klaviyoLogger->log('cleanLog failed: unable to parse timestamp from: ' . $date);
            return $response;
        }

        //get log file path and do the old switcheroo in preparation for cleaning
        $path = $this->_klaviyoLogger->getPath();
        $old = $path . '.old';
        rename($path, $old);

        //open file streams
        $input = fopen($old, 'rb');
        $output = fopen($path, 'wb');

        //loop through all of the lines in the log
        while ($row = fgets($input)) {
            //parse timestamp from the line in the log
            //example formatting:
            //[2018-07-05 11:10:35] channel-name.INFO: This is a log entry
            preg_match('/\[.*?\]/', $row, $matches);
            $timestamp = strtotime(substr($matches[0], 1, -1));
            if ($timestamp > $cursor) {
                fwrite($output, $row);
            }
        }


        //close file streams
        fclose($input);
        fclose($output);

        //remove old log file
        unlink($old);

        //log cleaning success
        $this->_klaviyoLogger->log('Cleaned all log entries before: ' . $date);

        //return success message
        $response = array(
            'message' => 'Cleaned all log entries before: ' . $date
        );
        return $response;
    }

    /**
     * Appends a message to the Klaviyo log file
     *
     * @api
     * @param string $message
     * @return array
     */
    public function appendLog($message)
    {
        //log the provided message
        $this->_klaviyoLogger->log($message);

        //return success message
        return array(
            'message' => 'Logged message: \'' . $message . '\''
        );
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
        foreach ($stores as $store) {
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
    public function product($quote_id, $item_id)
    {

        if (!$quote_id || !$item_id) {
            throw new NotFoundException(__('quote id or item id not found'));
        }

        $quote = $this->quoteFactory->create()->load($quote_id);
        if (!$quote) {
            throw new NotFoundException(__('quote not found'));
        }

        $item = $quote->getItemById($item_id);
        if (!$item) {
            throw new NotFoundException(__('item not found'));
        }

        $product = $this->_objectManager->get('Magento\Catalog\Model\Product')->load($item->getProductId());

        $image_array = $this->_getImages($product);

        $response['$body'] = array(
            'id' => $item->getProductId(),
            'images' => $image_array
        );

        return $response;
    }

    /**
    * @return mixed
    */
    public function productVariantInventory($product_id, $store_id = 0)
    {
        if (!$product_id) {
            throw new NotFoundException(_('A product id is required'));
        }
        // if store_id is specified, use it
        if ($store_id) {
            $product = $this->_productFactory->create()->setStoreId($store_id)->load($product_id);
        } else {
            $product = $this->_productFactory->create()->load($product_id);
        }

        if (!$product) {
            throw new NotFoundException(_('A product with id ' . $product_id . ' was not found'));
        }

        $productId = $product->getId();

        $response = array(array(
            'id' => $productId,
            'sku' => $product->getSku(),
            'title' => $product->getName(),
            'price' => $product->getPrice(),
            'available' => true,
            'inventory_quantity' => $this->_stockItem->getStockQty($productId),
            'inventory_policy' => $this->_getStockItem($productId)
        ));
        // check to see if the product has variants, if it doesn't just return the product information
        try {
            $_children = $product->getTypeInstance()->getUsedProducts($product);
            // throws a fatal error, so catch it generically and return
        } catch (\Error $e) {
            return $response;
        }

        foreach ($_children as $child) {
            $response['variants'][] = array(
                'id' => $child->getId(),
                'title' => $child->getName(),
                'sku' => $child->getSku(),
                'available' => $child->isAvailable(),
                'inventory_quantity' => $this->_stockItem->getStockQty($child->getId()),
                'inventory_policy' => $this->_getStockItem($child->getId()),
            );
        }

        return $response;
    }

    // handle inspector tasks to return products by id
    public function productinspector($start_id, $end_id)
    {

        if (($end_id - $start_id) > 100) {
            throw new NotFoundException(__('100 is the max batch'));
        } elseif (!$start_id || !$end_id) {
            throw new NotFoundException(__('provide a start and end filter'));
        }

        $response = array();
        foreach (range($start_id, $end_id) as $number) {
            $product = $this->_objectManager
                ->create('Magento\Catalog\Model\Product')
                ->load($number);

            if (!$product) {
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

    public function getSubscribersCount()
    {
        $subscriberCount = $this->_subscriberCollection->create()->getSize();
        return $subscriberCount;
    }

    public function getSubscribersById($start_id, $end_id, $storeId = null)
    {
        if (!isset($start_id) || !isset($end_id)) {
            throw new NotFoundException(__('Please provide start_id and end_id'));
        }

        if ($start_id > $end_id) {
            throw new NotFoundException(__('end_id should be larger than start_id'));
        }

        if (($end_id - $start_id) > self::SUBSCRIBER_BATCH_SIZE) {
            throw new NotFoundException(__('Max batch size is 500'));
        }

        $storeIdFilter = $this->_storeFilter($storeId);

        $subscriberCollection = $this->_subscriberCollection->create()
            ->addFieldToFilter('subscriber_id', ['gteq' => (int)$start_id])
            ->addFieldToFilter('subscriber_id', ['lteq' => (int)$end_id])
            ->addFieldToFilter('store_id', [$storeIdFilter => $storeId]);

        $response = $this->_packageSubscribers($subscriberCollection);

        return $response;
    }

    public function getSubscribersByDateRange($start, $until, $storeId = null)
    {

        if (!$start || !$until) {
            throw new NotFoundException(__('Please provide start and until param'));
        }
        // start and until date formats
        // $until = '2019-04-25 18:00:00';
        // $start = '2019-04-25 00:00:00';

        $until_date = strtotime($until);
        $start_date = strtotime($start);
        if (!$until_date || !$start_date) {
            throw new NotFoundException(__('Please use a valid date format YYYY-MM-DD HH:MM:SS'));
        }

        // don't want any big queries, we limit to 10 days
        $datediff = $until_date - $start_date;

        if (abs(round($datediff / (60 * 60 * 24))) > self::MAX_QUERY_DAYS) {
            throw new NotFoundException(__('Cannot query more than 10 days'));
        }

        $storeIdFilter = $this->_storeFilter($storeId);

        $subscriberCollection = $this->_subscriberCollection->create()
            ->addFieldToFilter('change_status_at', ['gteq' => $start])
            ->addFieldToFilter('change_status_at', ['lteq' => $until])
            ->addFieldToFilter('store_id', [$storeIdFilter => $storeId]);

        $response = $this->_packageSubscribers($subscriberCollection);

        return $response;
    }
    public function _packageSubscribers($subscriberCollection)
    {
        $response = array();
        foreach ($subscriberCollection as $subscriber) {
            $response[] = array(
                'email' => $subscriber->getEmail(),
                'subscribe_status' => $subscriber->getSubscriberStatus()
            );
        }
        return $response;
    }

    public function _storeFilter($storeId)
    {
        $storeIdFilter = 'eq';
        if (!$storeId) {
            $storeIdFilter = 'nlike';
        }
        return $storeIdFilter;
    }

    public function _getImages($product)
    {
        $images = $product->getMediaGalleryImages();
        $image_array = array();

        foreach ($images as $image) {
            $image_array[] = $this->handleMediaURL($image);
        }
        return $image_array;
    }
    public function handleMediaURL($image)
    {
        $custom_media_url = $this->_klaviyoScopeSetting->getCustomMediaURL();
        if ($custom_media_url) {
            return rtrim($custom_media_url, '/') . '/media/catalog/product' . $image->getFile();
        }
        return $image->getUrl();
    }
    public function _getStockItem($productId)
    {
        $stock = $this->_stockItemRepository->get($productId);
        return $stock->getManageStock();
    }
}
