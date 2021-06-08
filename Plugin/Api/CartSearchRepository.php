<?php


namespace Klaviyo\Reclaim\Plugin\Api;


use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Model\QuoteIdToMaskedQuoteId;
use Magento\Framework\Api\SearchResults;

class CartSearchRepository
{

    const KL_MASKED_ID = 'kl_masked_id';

    /**
     * QuoteId Masker
     *
     * @var QuoteIdToMaskedQuoteId
     */
    private $quoteIdMask;

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
     * @param QuoteIdToMaskedQuoteId $quoteIdToMaskedQuoteId
     */
    public function __construct(
        CartExtensionFactory $extensionFactory,
        QuoteIdToMaskedQuoteId $quoteIdToMaskedQuoteId
    ){
        $this->extensionFactory = $extensionFactory;
        $this->quoteIdMask = $quoteIdToMaskedQuoteId;
    }
    /**
     * Add "kl_masked_id" extension attribute to order data object to make it accessible in API data
     *
     * @param CartRepositoryInterface $subject
     * @param SearchResults $searchResult
     *
     * @return SearchResults
     */
    public function afterGetList(CartRepositoryInterface $subject, SearchResults $searchResult)
    {
        $quotes = $searchResult->getItems();

        foreach ($quotes as $quote) {
            $maskedId = $this->getMaskedIdFromQuoteId($quote->getId());
            $extensionAttributes = $quote->getExtensionAttributes();
            $extensionAttributes = $extensionAttributes ? $extensionAttributes : $this->extensionFactory->create();
            $extensionAttributes->setData(self::KL_MASKED_ID, $maskedId);
            $quote->setExtensionAttributes($extensionAttributes);
        }

        return $searchResult;
    }

    public function getMaskedIdFromQuoteId($quoteId)
    {
        try {
            $quoteId = $this->quoteIdMask->execute($quoteId);
        } catch (NoSuchEntityException $e) {
            $quoteId = "";
        }
        return $quoteId;
    }
}
