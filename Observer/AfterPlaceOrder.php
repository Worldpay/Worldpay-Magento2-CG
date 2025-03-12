<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Observer;

use Magento\Framework\Event\ObserverInterface;

class AfterPlaceOrder implements ObserverInterface
{
   
    /**
     * @var \Sapient\Worldpay\Helper\Recurring
     */
    private $wplogger;

     /**
      * @var \Magento\Quote\Api\CartRepositoryInterface
      */
    private $quoteRepository;

     /**
      * @var \Magento\Checkout\Model\Session
      */
    private $checkoutSession;

     /**
      * @var \Magento\Customer\Model\Session
      */
    private $customerSession;
    
    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    private $responseFactory;

    /**
     * @var \Magento\Customer\CustomerData\SectionPoolInterface
     */
    private $sectionPoolInterface;

    /**
     * Constructor function
     *
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Controller\ResultFactory $responseFactory
     * @param \Magento\Customer\CustomerData\SectionPoolInterface $sectionPoolInterface
     */

    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Controller\ResultFactory $responseFactory,
        \Magento\Customer\CustomerData\SectionPoolInterface $sectionPoolInterface
    ) {
        $this->wplogger = $wplogger;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->responseFactory = $responseFactory;
        $this->sectionPoolInterface = $sectionPoolInterface;
    }

    /**
     * Restore Cart
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $methodCode = $method->getCode();
        if ($methodCode == 'worldpay_paybylink') {
            $this->checkoutSession->unsPayByLinkRedirecturl();
        }
        $this->wplogger->info("#########################################");
        $this->wplogger->info($this->customerSession->getActiveQuoteId() ?? '');
        $this->wplogger->info("#########################################");
        if (!empty($this->customerSession->getActiveQuoteId())) {
            if ($order->getId()) {
                try {
                    /** @var $inActiveQuote \Magento\Quote\Model\Quote */
                    $inActiveQuoteId = $this->customerSession->getActiveQuoteId();
                    $inActiveQuote = $this->quoteRepository->get($inActiveQuoteId);
                    $inActiveQuote->setIsActive(1)->setReservedOrderId(null);
                    $this->quoteRepository->save($inActiveQuote);
                    /** @var $session \Magento\Checkout\Model\Session */
                    $this->checkoutSession->replaceQuote($inActiveQuote);
                    $this->checkoutSession->getQuote()->collectTotals();
                } catch (\Exception $e) {
                    $this->wplogger->info(
                        "Error while restoring cart: Quote Id:".$this->customerSession->getActiveQuoteId()
                    );
                    $this->wplogger->info($e->getMessage());
                }
                $this->customerSession->unsActiveQuoteId();
            }
        }
        return $this;
    }
}
