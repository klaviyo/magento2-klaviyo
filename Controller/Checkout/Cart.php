<?php

namespace Klaviyo\Reclaim\Controller\Checkout;

use Magento\Checkout\Model\Cart as CartModel;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Quote\Model\QuoteRepository;
use Psr\Log\LoggerInterface;

class Cart extends Action
{
    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;
    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;
    /**
     * @var CartModel
     */
    protected $cart;
    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        CartModel $cart,
        QuoteRepository $quoteRepository,
        RedirectFactory $resultRedirectFactory,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        LoggerInterface $logger
    ) {
        parent::__construct($context);

        $this->cart = $cart;
        $this->quoteRepository = $quoteRepository;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->logger = $logger;
    }

    /**
     * Endpoint /reclaim/checkout/cart resolves here. This endpoint will load an existing
     * quote into the current Customer's cart and redirect the Customer to checkout/cart
     * If no masked quote is found it will not do anything to the Customer's cart
     *
     * @return Redirect
     */
    public function execute()
    {
        $params = $this->_request->getParams();
        $maskedQuoteId = $params['quote_id'] ?? '';

        unset($params['quote_id']);

        try {
            $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedQuoteId);
            $quote = $this->quoteRepository->get($cartId);
            $this->cart->setQuote($quote);
            $this->cart->save();
        } catch (NoSuchEntityException $e) {
            $this->logger->warning($e);
        }

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('checkout/cart', ['_query' => $params]);
        return $redirect;
    }
}
