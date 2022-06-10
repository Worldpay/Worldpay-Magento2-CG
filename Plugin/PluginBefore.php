<?php
/**
 * PluginBefore @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Backend\Block\Widget\Button\Toolbar as ToolbarContext;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Backend\Block\Widget\Button\ButtonList;
use Sapient\Worldpay\Helper\Data;

class PluginBefore
{

    /**
     * Constructor
     *
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Sales\Model\Order $order
     * @param RequestInterface $request
     * @param Data $worldpayHelper
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Sales\Model\Order $order,
        RequestInterface $request,
        Data $worldpayHelper
    ) {
        $this->_urlBuilder = $urlBuilder;
        $this->order = $order;
        $this->request = $request;
        $this->worldpayHelper = $worldpayHelper;
    }
    /**
     * [beforePushButtons description]
     *
     * @param  ToolbarContext                                  $toolbar    [description]
     * @param  \Magento\Framework\View\Element\AbstractBlock   $context    [description]
     * @param  \Magento\Backend\Block\Widget\Button\ButtonList $buttonList [description]
     * @return [type]                                                      [description]
     */
    public function beforePushButtons(
        ToolbarContext $toolbar,
        \Magento\Framework\View\Element\AbstractBlock $context,
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
    ) {
        $this->_request = $context->getRequest();
        if ($this->_request->getFullActionName() == 'sales_order_view' && $this->worldpayHelper->isWorldPayEnable()) {
            $requestdata = $this->request->getParams();
            $orderId = $requestdata['order_id'];
            $syncurl = $this->_urlBuilder->getUrl("worldpay/syncstatus/index", ['order_id' => $orderId]);
            $order = $this->order->load($orderId);
            if ($order->getPayment()->getMethod()=='worldpay_cc'
                || $order->getPayment()->getMethod()=='worldpay_apm'
                || $order->getPayment()->getMethod()=='worldpay_moto'
                || $order->getPayment()->getMethod()=='worldpay_wallets'
                || $order->getPayment()->getMethod()=='worldpay_cc_vault') {
                $buttonList->add(
                    'sync_status',
                    ['label' => __('Sync Status'), 'onclick' => 'setLocation("'.$syncurl.'")', 'class' => 'reset'],
                    -1
                );
            }
             
            //Cancel button function to send order-modification request to Cancel Order.
            $cancelurl = $this->_urlBuilder->getUrl(
                "worldpay/cancel/index",
                ['order_id' => $orderId]
            );
            $buttonList->remove('order_cancel');
            $buttonList->add('cancel', ['label' => __('Cancel'),
                        'onclick' => 'setLocation("' . $cancelurl . '")',
                        'class' => 'cancel'], -1);
            //Void Sale changes
            $data = $order->getData();
            $paymenttype = $this->getPaymentType($data['increment_id']);
            if ($this->checkEligibilityForVoidSale($order)) {
                    $buttonList->remove('void_payment');
                    $voidsaleurl = $this->_urlBuilder->getUrl(
                        "worldpay/voidsale/index",
                        ['order_id' => $orderId]
                    );
                    $buttonList->add(
                        'void_sale',
                        ['label' => __('Void Sale'),
                        'onclick' => 'setLocation("' . $voidsaleurl . '")',
                        'class' => 'void'],
                        -1
                    );
            }
            if ($paymenttype === 'ACH_DIRECT_DEBIT-SSL' || $this->isPrimeRoutingRequest($data['increment_id'])) {
                $buttonList->remove('void_payment');
                $this->removeShipmentButton($order, $buttonList);
            }
        }

        return [$context, $buttonList];
    }
    /**
     * [getOrderDateDetails description]
     *
     * @param  [type] $order [description]
     * @return [type]        [description]
     */
    public function getOrderDateDetails($order)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $orderdate=$order->getCreatedAt();
        $objDate = $objectManager->create(\Magento\Framework\Stdlib\DateTime\DateTime::class);
        $currentdate = $objDate->Date();
        $timeobj= $objectManager->create(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class);
        $formattedCurrentDate=$timeobj->formatDate($currentdate, \IntlDateFormatter::SHORT);
        $formattedOrderDate=$timeobj->formatDate($orderdate, \IntlDateFormatter::SHORT);
             
        if ($formattedCurrentDate===$formattedOrderDate) {
            return true;
        }
        
        return false;
    }
    /**
     * [getPaymentType description]
     *
     * @param  [type] $orderid [description]
     * @return [type]          [description]
     */
    public function getPaymentType($orderid)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $worldpaymodel = $objectManager->create(\Sapient\Worldpay\Model\Worldpayment::class);
        $worldpaydata=$worldpaymodel->loadByPaymentId($orderid);
        return $worldpaydata->getPaymentType();
    }
    /**
     * [checkEligibilityForVoidSale description]
     *
     * @param  [type] $order [description]
     * @return [type]        [description]
     */
    public function checkEligibilityForVoidSale($order)
    {
        $data = $order->getData();
        $paymenttype = $this->getPaymentType($data['increment_id']);
        $orderStatus = $order->getStatus();
        if (strtoupper($orderStatus)==='PENDING' || strtoupper($orderStatus)==='PROCESSING') {
            if ($order->getPayment()->getMethod() == 'worldpay_apm'
                    && $paymenttype === 'ACH_DIRECT_DEBIT-SSL'
                    && $this->getOrderDateDetails($order)) {
                return true;
            } elseif ($this->isPrimeRoutingRequest($data['increment_id']) && $this->getOrderDateDetails($order)) {
                return true;
            }
        }
    }
    /**
     * [isPrimeRoutingRequest description]
     *
     * @param  [type]  $orderid [description]
     * @return boolean          [description]
     */
    public function isPrimeRoutingRequest($orderid)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $worldpaymodel = $objectManager->create(\Sapient\Worldpay\Model\Worldpayment::class);
        $worldpaydata=$worldpaymodel->loadByPaymentId($orderid);
      
        if ($worldpaydata->getIsPrimeroutingEnabled()) {
            return true;
        }
    }
    /**
     * [removeShipmentButton description]
     *
     * @param  [type] $order      [description]
     * @param  [type] $buttonList [description]
     * @return [type]             [description]
     */
    public function removeShipmentButton($order, $buttonList)
    {
        $orderStatus = $order->getStatus();
        if (strtoupper($orderStatus) === 'CLOSED') {
                    $buttonList->remove('order_ship');
        }
    }
}
