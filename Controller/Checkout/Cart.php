<?php

namespace Klaviyo\Reclaim\Controller\Checkout;

class Cart extends \Magento\Framework\App\Action\Action
{
    const CART_QUOTE_ID = 'quote_id';

    protected $quoteRepository;
    protected $resultRedirectFactory;
    protected $cart;
    protected $request;

    public function __construct(
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Quote\Model\QuoteRepository $quoteRepository
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->cart = $cart;
        $this->request = $context->getRequest();

        parent::__construct($context);
    }

    /**
     * Endpoint /reclaim/checkout/cart resolves here. This endpoint will load an existing
     * quote into the current Customer's cart and redirect the Customer to checkout/cart
     * If no quote is found it will not do anything to the Customer's cart
     *
     * @return JSON
     */
    public function execute()
    {
        $params = $this->request->getParams();
        $quoteId = $params[Cart::CART_QUOTE_ID];
        unset($params[Cart::CART_QUOTE_ID]);

        try {
          $quote = $this->quoteRepository->get($quoteId);
          $this->cart->setQuote($quote);
          $this->cart->save();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $ex) {
        }

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('checkout/cart', $params);
        return $redirect;
    }
}
