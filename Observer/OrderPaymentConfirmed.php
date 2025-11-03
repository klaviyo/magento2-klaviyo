<?php

namespace Klaviyo\Reclaim\Observer;

use Klaviyo\Reclaim\Helper\Data;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Model\Events;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Invoice;

class OrderPaymentConfirmed implements ObserverInterface
{
    // Character limit for a TEXT datatype field
    const PAYLOAD_CHARACTER_LIMIT = 65535;
    const INTEGRATION_KEY = 'magento_two';

    /**
     * Klaviyo Scope setting Helper
     * @var ScopeSetting
     */
    protected $_scopeSetting;

    /**
     * Klaviyo Data Helper
     * @var Data
     */
    protected $_dataHelper;

    /**
     * Klaviyo Logger
     * @var Logger
     */
    protected $_klaviyoLogger;

    /**
     * Events Model
     * @var Events
     */
    protected $_eventsModel;

    /**
     * OrderPaymentConfirmed constructor
     * @param Logger $klaviyoLogger
     * @param ScopeSetting $scopeSetting
     * @param Data $dataHelper
     * @param Events $eventsModel
     */
    public function __construct(
        Logger $klaviyoLogger,
        ScopeSetting $scopeSetting,
        Data $dataHelper,
        Events $eventsModel
    ) {
        $this->_klaviyoLogger = $klaviyoLogger;
        $this->_scopeSetting = $scopeSetting;
        $this->_dataHelper = $dataHelper;
        $this->_eventsModel = $eventsModel;
    }

    /**
     * Handles the sales_order_invoice_pay event to track payment confirmation
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        // Check if the extension is configured with API key
        if (empty($this->_scopeSetting->getPrivateApiKey())) {
            return;
        }

        // Check if the cookie is set
        if (!isset($_COOKIE['__kla_id'])) {
            return;
        }

        $kl_decoded_cookie = json_decode(base64_decode($_COOKIE['__kla_id']), true);

        // Make sure we have an identifier for the customer set in the cookie
        if (isset($kl_decoded_cookie['$exchange_id'])) {
            $kl_user_properties = ['$exchange_id' => $kl_decoded_cookie['$exchange_id']];
        } elseif (!empty($kl_decoded_cookie['$email'])) {
            $kl_user_properties = ['$email' => $kl_decoded_cookie['$email']];
        } else {
            return;
        }

        /** @var Invoice $invoice */
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();

        // Build the order payment confirmed event payload
        $orderPaymentData = $this->buildOrderPaymentPayload($order, $invoice);

        $stringifiedPayload = json_encode($orderPaymentData);

        // Check payload length to avoid truncated data being saved to payload column
        if (strlen($stringifiedPayload) > self::PAYLOAD_CHARACTER_LIMIT) {
            $this->_klaviyoLogger->log(sprintf("[OrderPaymentConfirmed] Dropping event - payload too long, character count: %d", strlen($stringifiedPayload)));
        } else {
            $newEvent = [
                'status' => 'NEW',
                'user_properties' => json_encode($kl_user_properties),
                'event' => 'Order Payment Confirmed',
                'payload' => $stringifiedPayload
            ];

            try {
                // Creating a new row in the kl_events table
                $eventsData = $this->_eventsModel->setData($newEvent);
                $eventsData->save();
            } catch (\Exception $e) {
                $this->_klaviyoLogger->log(sprintf("[OrderPaymentConfirmed] Unable to save row to kl_events: %s", $e->getMessage()));
            }
        }
    }

    /**
     * Build the order payment confirmed event payload
     *
     * @param \Magento\Sales\Model\Order $order
     * @param Invoice $invoice
     * @return array
     */
    private function buildOrderPaymentPayload($order, $invoice)
    {
        $items = [];
        $itemNames = [];
        $categories = [];

        foreach ($order->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $itemCategories = $product ? $product->getCategoryIds() : [];

            $items[] = [
                'ProductId' => (int) $item->getProductId(),
                'Sku' => (string) $item->getSku(),
                'ProductName' => (string) $item->getName(),
                'Quantity' => (float) $item->getQtyOrdered(),
                'ItemPrice' => (float) $item->getPrice(),
                'RowTotal' => (float) $item->getRowTotal(),
                'ProductCategories' => (array) $itemCategories
            ];

            $itemNames[] = $item->getName();
            $categories = array_unique(array_merge($categories, $itemCategories));
        }

        $payload = [
            'OrderId' => (string) $order->getIncrementId(),
            'OrderNumber' => (string) $order->getIncrementId(),
            'StoreId' => (int) $order->getStoreId(),
            'CustomerEmail' => (string) $order->getCustomerEmail(),
            'CustomerName' => (string) $order->getCustomerName(),
            'PaymentMethod' => (string) $order->getPayment()->getMethod(),
            'InvoiceId' => (string) $invoice->getIncrementId(),
            'OrderStatus' => (string) $order->getStatus(),
            'OrderState' => (string) $order->getState(),
            'SubTotal' => (float) $order->getSubtotal(),
            'Tax' => (float) $order->getTaxAmount(),
            'Shipping' => (float) $order->getShippingAmount(),
            'Discount' => (float) $order->getDiscountAmount(),
            'GrandTotal' => (float) $order->getGrandTotal(),
            'Items' => $items,
            'ItemNames' => $itemNames,
            'Categories' => array_values($categories),
            'time' => time(),
            'integration_key' => self::INTEGRATION_KEY
        ];

        // Setting external_catalog_id in payload
        $payload['external_catalog_id'] = $this->_dataHelper->getExternalCatalogIdForEvent(
            $order->getStore()->getWebsiteId(),
            $order->getStoreId()
        );

        return $payload;
    }
}
