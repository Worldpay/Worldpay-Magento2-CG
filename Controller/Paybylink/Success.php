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
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     * @param \Magento\Sales\Model\Order $orderItemsDetails
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        \Sapient\Worldpay\Model\Order\Service $orderservice,
        \Magento\Sales\Model\Order $orderItemsDetails,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->pageFactory = $pageFactory;
        $this->orderservice = $orderservice;
        $this->wplogger = $wplogger;
        $this->orderItemsDetails = $orderItemsDetails;
        $this->_checkoutSession = $checkoutSession;
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
        $this->wplogger->info('Params :  '.json_encode($params, true));
        if (!empty($params['orderKey'])) {
            preg_match('/\^(\d+)-/', $params['orderKey'], $matches);
            $order = $this->orderItemsDetails->loadByIncrementId($matches[1]);
            if ($order->getId()) {
                $this->setOrderSessionData($order);
                $this->orderservice->redirectOrderSuccess();
                $this->orderservice->removeAuthorisedOrder();
                return $this->resultRedirectFactory->create()
                ->setPath('checkout/onepage/success', ['_current' => true]);
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
