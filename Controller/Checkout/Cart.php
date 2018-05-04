<?php

namespace Klaviyo\Reclaim\Controller\Checkout;

class Cart extends \Magento\Framework\App\Action\Action
{
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
        $quoteId = $this->request->getParam('quote_id');

        try {
          $quote = $this->quoteRepository->get($quoteId);
          $this->cart->setQuote($quote);
          $this->cart->save();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $ex) {
        }

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('checkout/cart', ['_current' => true]);
        return $redirect;
    }
}
