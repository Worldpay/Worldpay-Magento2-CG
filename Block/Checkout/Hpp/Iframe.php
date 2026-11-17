<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Block\Checkout\Hpp;

class Iframe extends \Magento\Framework\View\Element\Template
{
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $data['template'] = 'Sapient_Worldpay::checkout/hpp/iframe.phtml';
        parent::__construct($context, $data);
    }
}
