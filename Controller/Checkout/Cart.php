<?php

namespace Klaviyo\Reclaim\Controller\Checkout;

use Magento\Framework\Exception\NoSuchEntityException;

class Cart extends \Magento\Framework\App\Action\Action
{
    protected $quoteRepository;
    protected $resultRedirectFactory;
    protected $cart;
    protected $request;

    /**
     * @var quoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    public function __construct(
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->cart = $cart;
        $this->request = $context->getRequest();
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;

        parent::__construct($context);
    }

    /**
     * Endpoint /reclaim/checkout/cart resolves here. This endpoint will load an existing
     * quote into the current Customer's cart and redirect the Customer to checkout/cart
     * If no masked quote is found it will not do anything to the Customer's cart
     *
     * @return JSON
     */
    public function execute()
    {
        $params = $this->request->getParams();
        $quoteId = isset($params['quote_id']) ? $params['quote_id'] : "";

        unset($params['quote_id']);

        // Check if the quote_id has kx_identifier, if yes, retrieve active quote for customer, if not get QuoteId from masked QuoteId
        if (strpos($quoteId, "kx_identifier_") !== false) {
            $customerId = base64_decode(str_replace("kx_identifier_", "", $quoteId));
            try {
                $quote = $this->quoteRepository->getActiveForCustomer($customerId);
                $this->cart->setQuote($quote);
                $this->cart->save();
            } catch (NoSuchEntityException $ex) {
            }
        } else {
            try {
                $quoteIdMask = $this->quoteIdMaskFactory->create()->load($quoteId, 'masked_id');
                $quote = $this->quoteRepository->get($quoteIdMask->getQuoteId());
                $this->cart->setQuote($quote);
                $this->cart->save();
            } catch (NoSuchEntityException $ex) {
            }
        }

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('checkout/cart', ['_query' => $params]);
        return $redirect;
    }
}
