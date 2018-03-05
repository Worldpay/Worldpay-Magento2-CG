<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Backend\Block\Widget\Button\Toolbar as ToolbarContext;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Backend\Block\Widget\Button\ButtonList;

class PluginBefore
{

    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Sales\Model\Order $order,
        RequestInterface $request
    ) {
        $this->_urlBuilder = $urlBuilder;
        $this->order = $order;
        $this->request = $request;
    }

    public function beforePushButtons(
        ToolbarContext $toolbar,
        \Magento\Framework\View\Element\AbstractBlock $context,
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
    ) {
        $this->_request = $context->getRequest();
        if ($this->_request->getFullActionName() == 'sales_order_view') {
            $requestdata = $this->request->getParams();
            $orderId = $requestdata['order_id'];
            $syncurl = $this->_urlBuilder->getUrl("worldpay/syncstatus/index",array('order_id' => $orderId));
            $order = $this->order->load($orderId);
            if($order->getPayment()->getMethod()=='worldpay_cc'
                || $order->getPayment()->getMethod()=='worldpay_apm'
                || $order->getPayment()->getMethod()=='worldpay_moto') {
                $buttonList->add(
                    'sync_status',
                    ['label' => __('Sync Status'), 'onclick' => 'setLocation("'.$syncurl.'")', 'class' => 'reset'],
                    -1
                );
            }
        }

        return [$context, $buttonList];
    }
}
