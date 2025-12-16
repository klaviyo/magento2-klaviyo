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

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    public function __construct(
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->cart = $cart;
        $this->request = $context->getRequest();
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->customerSession = $customerSession;

        parent::__construct($context);
    }

    /**
     * Endpoint /reclaim/checkout/cart resolves here. This endpoint will load an existing
     * quote into the current Customer's cart and redirect the Customer to checkout/cart
     * If no masked quote is found it will not do anything to the Customer's cart
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $params = $this->request->getParams();
        $quoteId = isset($params['quote_id']) ? $params['quote_id'] : "";

        unset($params['quote_id']);

        // Check if the quote_id has kx_identifier, if yes, retrieve active quote for customer
        if (strpos($quoteId, "kx_identifier_") !== false) {
            $customerId = base64_decode(str_replace("kx_identifier_", "", $quoteId));
            try {
                $quote = $this->quoteRepository->getActiveForCustomer($customerId);
                $this->cart->setQuote($quote);
                $this->cart->save();
            } catch (NoSuchEntityException $ex) {
                // Quote not found for customer
            }
        } else {
            $this->loadQuoteByIdOrMaskedId($quoteId);
        }

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('checkout/cart', ['_query' => $params]);
        return $redirect;
    }

    /**
     * Attempts to load a quote by masked ID first, falling back to unmasked ID for logged-in customers.
     *
     * Security: Unmasked quote IDs are only accepted if the quote belongs to the currently
     * logged-in customer to prevent cart enumeration attacks.
     *
     * @param string $quoteId The quote identifier (masked or unmasked)
     */
    private function loadQuoteByIdOrMaskedId(string $quoteId): void
    {
        // First, try to load as a masked quote ID (standard case for guest carts)
        try {
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($quoteId, 'masked_id');
            if ($quoteIdMask->getQuoteId()) {
                $quote = $this->quoteRepository->get($quoteIdMask->getQuoteId());
                $this->cart->setQuote($quote);
                $this->cart->save();
                return;
            }
        } catch (NoSuchEntityException $ex) {
            // Masked quote not found, continue to try unmasked
        }

        // If the quote_id is numeric, attempt to load as unmasked quote ID
        // This handles edge cases where logged-in customer carts don't have masked IDs
        if (!is_numeric($quoteId)) {
            return;
        }

        try {
            $quote = $this->quoteRepository->get((int)$quoteId);

            // Security check: Only allow loading if the quote belongs to the logged-in customer
            // This prevents enumeration attacks using sequential quote IDs
            $quoteCustomerId = $quote->getCustomerId();
            $currentCustomerId = $this->customerSession->getCustomerId();

            if ($quoteCustomerId && $currentCustomerId && $quoteCustomerId == $currentCustomerId) {
                $this->cart->setQuote($quote);
                $this->cart->save();
            }
            // If the quote has a customer ID but it doesn't match, silently ignore for security
            // Guest quotes (no customer ID) should use masked IDs for security
        } catch (NoSuchEntityException $ex) {
            // Quote not found
        }
    }
}
