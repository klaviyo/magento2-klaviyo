<?php

namespace Klaviyo\Reclaim\Controller\Checkout;


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

        try {
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($quoteId, 'masked_id');
            $quote = $this->quoteRepository->get($quoteIdMask->getQuoteId());
            $this->cart->setQuote($quote);
            $this->cart->save();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $ex) {
        }

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('checkout/cart', ['_query' => $params]);
        return $redirect;
    }
}
