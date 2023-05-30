<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Paybylink;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
 
 /**
  * remove authorized order from card and Redirect to success page
  */
class Success extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

    /**
     * @var Magento\Sales\Model\Order $orderItemsDetails
     */
    protected $orderItemsDetails;

    /**
     * @var \Sapient\Worldpay\Model\Order\Service
     */
    protected $orderservice;

    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $wplogger;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     * @param \Magento\Sales\Model\Order $orderItemsDetails
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        \Sapient\Worldpay\Model\Order\Service $orderservice,
        \Magento\Sales\Model\Order $orderItemsDetails,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Model\QuoteFactory $quoteFactory
    ) {
        $this->pageFactory = $pageFactory;
        $this->orderservice = $orderservice;
        $this->wplogger = $wplogger;
        $this->orderItemsDetails = $orderItemsDetails;
        $this->_checkoutSession = $checkoutSession;
        $this->quoteFactory = $quoteFactory;
        return parent::__construct($context);
    }

    /**
     * Order success action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->wplogger->info('worldpay returned Pay by link success url');
        $params = $this->getRequest()->getParams();
        if (!empty($params['orderKey'])) {
            preg_match('/\^(\d+)-/', $params['orderKey'], $matches);
            $order = $this->orderItemsDetails->loadByIncrementId($matches[1]);
            if ($order->getId()) {
                $this->setOrderSessionData($order);
                $this->orderservice->redirectOrderSuccess();
                $this->orderservice->removeAuthorisedOrder();
                $quote = $this->quoteFactory->create()->load($order->getQuoteId());
                $isMultiShipping = $quote->getIsMultiShipping();
                if ($isMultiShipping) {
                    $url = 'worldpay/paybylink_multishipping/success?orderKey='.$params['orderKey'];
                    return $this->resultRedirectFactory->create()
                    ->setPath($url);
                } else {
                    return $this->resultRedirectFactory->create()
                    ->setPath('checkout/onepage/success', ['_current' => true]);
                }
            } else {
                $this->wplogger->info('Order not found.Redirecting to checkout cart page');
                return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => true]);
            }
        } else {
            $this->wplogger->info('Redirect to checkout cart page');
            return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => true]);
        }
    }
    /**
     * Set Order Session data
     *
     * @param Order $order
     * @return void
     */
    protected function setOrderSessionData($order)
    {
        if ($order->getId()) {
                $this->wplogger->info('Order Exists:  '.$order->getIncrementId());
                $this->_checkoutSession->setauthenticatedOrderId($order->getIncrementId());
                //$this->_checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
                $this->_checkoutSession->setLastOrderId($order->getId());
                $this->_checkoutSession->setLastQuoteId($order->getQuoteId());
                $this->_checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
                $this->_checkoutSession->setLastOrderId($order->getEntityId());
                $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
                $this->_checkoutSession->setLastOrderStatus($order->getStatus());
        }
    }
}
