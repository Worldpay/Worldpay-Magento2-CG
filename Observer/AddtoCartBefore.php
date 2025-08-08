<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;

class AddtoCartBefore implements ObserverInterface
{

    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    private $wplogger;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;
     /**
      * @var \Magento\Customer\Model\Session
      */
    private $customerSession;

     /**
      * @var \Magento\Checkout\Model\Cart
      */
    private $customerCart;

     /**
      * @var \Sapient\Worldpay\Helper\Data
      */
    private $wpHelper;

     /**
      * @var \Magento\Quote\Model\QuoteManagement
      */
    private $quoteManagement;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;
    /**
     * Constructor function
     *
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Cart $customerCart
     * @param \Sapient\Worldpay\Helper\Data $wpHelper
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param CustomerRepository $customerRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Cart $customerCart,
        \Sapient\Worldpay\Helper\Data $wpHelper,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        CustomerRepository $customerRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->wplogger = $wplogger;
        $this->quoteRepository = $quoteRepository;
        $this->customerSession = $customerSession;
        $this->customerCart = $customerCart;
        $this->wpHelper  = $wpHelper;
        $this->quoteManagement = $quoteManagement;
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Restore Cart
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (($this->wpHelper->isGooglePayEnableonPdp() && $this->wpHelper->isGooglePayEnable())
            || ($this->wpHelper->isApplePayEnableonPdp() && $this->wpHelper->isApplePayEnable() )) {
            $product = $observer->getEvent()->getProduct();
            $productRequestInfo = $observer->getEvent()->getInfo();
            if (!empty($productRequestInfo['existing_quote_id']) && $productRequestInfo['existing_quote_id'] !=0) {
                $this->customerSession->unsActiveQuoteId();
                try {
                    // get current Quote Id  and make inactive
                    // add current quote Id in session
                    //$currentQuoteId = $productRequestInfo['existing_quote_id'];
                    $currentQuoteId = $this->checkoutSession->getQuote()->getId();
                    $this->customerSession->setActiveQuoteId($currentQuoteId);
                    $activeQuote = $this->quoteRepository->get($currentQuoteId);
                    $activeQuote->setIsActive(0);
                    $this->quoteRepository->save($activeQuote);
                    if ($this->customerSession->getCustomerId()) {

                        $newQuoteID = $this->quoteManagement->createEmptyCartForCustomer(
                            $this->customerSession->getCustomerId()
                        );
                        $newQuote = $this->quoteRepository->get($newQuoteID);
                        $this->customerCart->setQuote($newQuote);
                        $this->wplogger->info("Set New Quote successfully");
                    } else {

                        $newQuoteID = $this->quoteManagement->createEmptyCart();
                        $newQuote = $this->quoteRepository->get($newQuoteID);
                        $this->customerCart->setQuote($newQuote);
                        $this->wplogger->info("Guest Quote : Set Quote successfully");
                    }

                } catch (\Exception $e) {
                    $this->wplogger->info(__("Error while making cart Inactive"). $e->getMessage());
                }
            }
        }
        return $this;
    }

    /**
     * Get store id
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }
}
