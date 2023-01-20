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
class Process extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Sapient\Worldpay\Model\PaymentMethods\PayByLink
     */
    protected $paybylink;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;
    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Sapient\Worldpay\Model\PaymentMethods\PayByLink $paybylink
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Magento\Framework\Controller\Result\Redirect $resultRedirectFactory
     * @param \Magento\Sales\Model\Order $orderItemsDetails
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\RequestInterface $request,
        \Sapient\Worldpay\Model\Order\Service $orderservice,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Sapient\Worldpay\Model\PaymentMethods\PayByLink $paybylink,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Framework\Controller\Result\Redirect $resultRedirectFactory,
        \Magento\Sales\Model\Order $orderItemsDetails
    ) {
        $this->request = $request;
        $this->orderservice = $orderservice;
        $this->quoteFactory = $quoteFactory;
        $this->paybylink = $paybylink;
        $this->wplogger = $wplogger;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->orderItemsDetails = $orderItemsDetails;

        return parent::__construct($context);
    }

    /**
     * Order Process action
     *
     * @return string
     */
    public function execute()
    {
        $this->wplogger->info('Pay by link Process controller executed.');
        $orderCode = $this->request->getParam('orderkey');
        $orderIncrementId = current(explode('-', $orderCode));
        $orderInfo = $this->orderItemsDetails->loadByIncrementId($orderIncrementId);
        if ($orderInfo->getId()) {
            if (strtolower($orderInfo->getStatus()) == 'canceled') {
                $this->messageManager->addNotice(__('Order not found or cancelled previously'));
                return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => true]);
            }

            $order = $this->orderservice->getByIncrementId($orderIncrementId);
            $magentoorder = $order->getOrder();
            $quoteId  = $magentoorder->getQuoteId();
            $quote  = $this->getPaybylinkquote($quoteId);
            $payment = $magentoorder->getPayment();
            $paymentDetails = [];
            $paymentDetails['additional_data']['cc_type'] = 'ALL';
            $paymentDetails['method'] = 'worldpay_paybylink';
            $authorisationService = $this->paybylink->getAuthorisationService($quote->getStoreId());
            $hppUrl = $authorisationService->authorizeRegenaretPayment(
                $magentoorder,
                $quote,
                $orderCode,
                $quote->getStoreId(),
                $paymentDetails,
                $payment
            );
            if (!empty($hppUrl['payment'])) {
                $this->orderservice->removeAuthorisedOrder();
                return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => false]);
            }
            return $this->_setredirectpaybylinkhpp($hppUrl);
        } else {
            $this->wplogger->info('Order not found.Redirecting to checkout cart page');
            return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => true]);
        }
    }

    /**
     * Get pay by link order
     *
     * @param string $orderKey
     * @return string
     */
    private function _getPaybylinkorder($orderKey)
    {
        return $this->orderservice->loadByIncrementId($orderKey);
    }

    /**
     * Get Pay by link quote
     *
     * @param int $quoteId
     * @return \Magento\Quote\Model\QuoteFactory
     */
    protected function getPaybylinkquote($quoteId)
    {
        return $this->quoteFactory->create()->load($quoteId);
    }
    
    /**
     * Set redirect pay by link hpp
     *
     * @param string $redirectLink
     * @return string
     */
    private function _setredirectpaybylinkhpp($redirectLink)
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($redirectLink);
        return $resultRedirect;
    }
}
