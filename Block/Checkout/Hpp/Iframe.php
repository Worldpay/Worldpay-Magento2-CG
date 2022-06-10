<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Block\Checkout\Hpp;
 
class Iframe extends \Magento\Framework\View\Element\Template
{
    /**
     * Set default template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('Sapient_Worldpay::checkout/hpp/iframe.phtml');
    }
}
