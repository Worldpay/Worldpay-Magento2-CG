<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class OrderStatus implements \Magento\Framework\Option\ArrayInterface
{
        
    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $_orderConfig;

    /**
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     */
    public function __construct(\Magento\Sales\Model\Order\Config $orderConfig)
    {
        $this->_orderConfig = $orderConfig;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'pending', 'label' => __('Pending')],
            ['value' => 'processing', 'label' => __('Processing')],
            ['value' => 'canceled', 'label' => __('Canceled')],
            ['value' => 'complete', 'label' => __('Complete')]
        ];
    }
}
