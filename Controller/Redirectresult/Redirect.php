<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Redirectresult;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;

/**
 * Redirect to the page return from worldpay
 */

class Redirect extends \Magento\Framework\App\Action\Action
{

   /**
    * @var Magento\Framework\View\Result\PageFactory
    */
    protected $pageFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutsession;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $mageOrder;

    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    protected $worldpayHelper;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $emailsender;

    /**
     * @var \Sapient\Worldpay\Model\Order\Service
     */

      protected $orderservice;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param \Magento\Checkout\Model\Session $checkoutsession
     * @param \Magento\Sales\Model\Order $mageOrder
     * @param \Sapient\Worldpay\Helper\Data $worldpayHelper
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $emailsender
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        \Magento\Checkout\Model\Session $checkoutsession,
        \Magento\Sales\Model\Order $mageOrder,
        \Sapient\Worldpay\Helper\Data $worldpayHelper,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $emailsender,
        \Sapient\Worldpay\Model\Order\Service $orderservice
    ) {
        $this->pageFactory = $pageFactory;
        $this->checkoutsession = $checkoutsession;
        $this->mageOrder = $mageOrder;
        $this->worldpayHelper = $worldpayHelper;
        $this->emailsender = $emailsender;
        $this->orderservice = $orderservice;
        return parent::__construct($context);
    }
   /**
    * Execute
    *
    * @return string
    */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->worldpayHelper->isWorldPayEnable()) {
            $resultRedirect->setPath('noroute');
            return $resultRedirect;
        }
        if ($this->worldpayHelper->getRedirectIntegrationMode() == 'full_page') {
            $order = $this->getAuthorisedOrder();
            $magentoorder = $order->getOrder();
            $order = $this->getAuthorisedOrder();
            $magentoorder = $order->getOrder();
            $this->emailsender->fullPageRedirectOrderEmail($magentoorder);
        }
        $redirecturl = $this->checkoutsession->getWpRedirecturl();
        $this->checkoutsession->unsWpRedirecturl();
        $this->checkoutsession->unsIframePay();
        $this->checkoutsession->unsHppOrderCode();
        return $resultRedirect->setUrl($redirecturl);
    }

    /**
     * Get Authorised Order
     *
     * @return Increament Id
     */
    public function getAuthorisedOrder()
    {
        if ($this->checkoutsession->getauthenticatedOrderId()) {
            return $this->orderservice->getByIncrementId($this->checkoutsession->getauthenticatedOrderId());
        }
        return false;
    }
}
