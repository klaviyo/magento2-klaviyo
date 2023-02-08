<?php

namespace Klaviyo\Reclaim\Plugin\Api;

use Klaviyo\Reclaim\Model\Quote\QuoteIdMask as QuoteIdMaskResource;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Model\CartSearchResults;

class CartSearchRepository
{
    const KL_MASKED_ID = 'kl_masked_id';

    /**
     * Quote Id Mask Resource
     *
     * @var QuoteIdMaskResource
     */
    private $quoteIdMaskResource;

    /**
     * Cart Extension Attributes Factory
     *
     * @var CartExtensionFactory
     */
    protected $extensionFactory;

    /**
     * CartRepositoryPlugin constructor
     *
     * @param CartExtensionFactory $extensionFactory
     * @param QuoteIdMaskResource $quoteIdMaskResource
     */
    public function __construct(
        CartExtensionFactory $extensionFactory,
        QuoteIdMaskResource $quoteIdMaskResource
    ) {
        $this->extensionFactory = $extensionFactory;
        $this->quoteIdMaskResource = $quoteIdMaskResource;
    }
    /**
     * Add "kl_masked_id" extension attribute to order data object to make it accessible in API data
     *
     * @param CartRepositoryInterface $subject
     * @param CartSearchResults $searchResult
     *
     * @return CartSearchResults
     */
    public function afterGetList(CartRepositoryInterface $subject, CartSearchResults $searchResult)
    {
        $quotes = $searchResult->getItems();

        foreach ($quotes as $quote) {
            $maskedId = $this->quoteIdMaskResource->getMaskedQuoteId($quote->getId());
            $extensionAttributes = $quote->getExtensionAttributes();
            $extensionAttributes = $extensionAttributes ? $extensionAttributes : $this->extensionFactory->create();
            $extensionAttributes->setData(self::KL_MASKED_ID, $maskedId);
            $quote->setExtensionAttributes($extensionAttributes);
        }

        return $searchResult;
    }
}
