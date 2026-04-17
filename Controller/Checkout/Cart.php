<?php

namespace Klaviyo\Reclaim\Controller\Checkout;

use Magento\Framework\Exception\NoSuchEntityException;

class Cart extends \Magento\Framework\App\Action\Action
{
    protected $resultRedirectFactory;
    protected $request;

    public function __construct(
        protected \Magento\Checkout\Model\Cart $cart,
        protected \Magento\Framework\App\Action\Context $context,
        protected \Magento\Quote\Model\QuoteRepository $quoteRepository,
        protected \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        protected \Magento\Customer\Model\Session $customerSession
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
        $redirect = $this->resultRedirectFactory->create();

        // Check if the quote_id has kx_identifier.
        // if yes, retrieve active quote for customer, otherwise
        // Check if the quote_id is masked or not, then pull it from the database
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
                if (!is_numeric($quoteId)) {
                    $quoteIdMask = $this->quoteIdMaskFactory
                        ->create()
                        ->load($quoteId, 'masked_id');
                    $quoteId = $quoteIdMask->getQuoteId();
                }
                $quote = $this->quoteRepository->get($quoteId);
                // Ensure the customer owns the quote
                if (
                    (int)$quote->getCustomerId() != 0 &&
                    (int)$quote->getCustomerId() != (int)$this->customerSession->getCustomerId()
                ) {
                    if ($this->customerSession->isLoggedIn()) {
                        $redirect->setPath('/');
                    } else {
                        $redirect->setPath('/customer/account/login');
                    }
                    return $redirect;
                }
                $this->cart->setQuote($quote);
                $this->cart->save();
            } catch (NoSuchEntityException $ex) {
            }
        }
        $redirect->setPath('checkout/cart', ['_query' => $params]);
        return $redirect;
    }
}
