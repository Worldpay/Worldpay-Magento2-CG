<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Wallets;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Exception;

class CancelCheckout extends \Magento\Framework\App\Action\Action implements HttpPostActionInterface
{
    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $wplogger;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultPageFactory;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        Context $context,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->wplogger = $wplogger;
        $this->_resultPageFactory = $resultJsonFactory;
        $this->quoteRepository = $quoteRepository;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;

        parent::__construct($context);
    }

    /**
     * Renders the 3D Secure  page, responsible for forwarding.
     *
     * All necessary order data to worldpay.
     */
    public function execute()
    {
        $result = $this->_resultPageFactory->create();
        try {
                // setting g-pay/apple-pay quote id to inactive
                $currentQuoteID = $this->getQuote()->getId();
            if ($currentQuoteID) {
                $this->wplogger->info("Current Active Cart ID:".$currentQuoteID);
                $activeQuote = $this->quoteRepository->get($currentQuoteID);
                $activeQuote->setIsActive(0);
                $this->quoteRepository->save($activeQuote);
            }

                // bring previous user cart to active state
            if ($this->customerSession->getActiveQuoteId()) {
                $this->wplogger->info("Previous  Active Cart ID:".$this->customerSession->getActiveQuoteId());
                $inActiveQuoteId = $this->customerSession->getActiveQuoteId();
                $inActiveQuote = $this->quoteRepository->get($inActiveQuoteId);
                $inActiveQuote->setIsActive(1)->setReservedOrderId(null);
                $this->quoteRepository->save($inActiveQuote);
                $this->checkoutSession->replaceQuote($inActiveQuote);
                $this->checkoutSession->getQuote()->collectTotals();
                // unsetting session variable
                $this->customerSession->unsActiveQuoteId();
            }
                    $resultData = [
                        'success' => true,
                        'time' => time(),
                    ];
        } catch (\Exception $e) {
            $resultData = [
                'success' => false,
                'time' => time(),
                'error_msg'=> $e->getMessage()
            ];
            $this->wplogger->info("Cancel wp wallet checkout: Failed while unsetting quote Id ".$e->getMessage());
        }
        return $result->setData($resultData);
    }

    /**
     * Get Quote
     */
    public function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }
}
