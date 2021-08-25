<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Block\Checkout\Hpp;
 
class Iframe extends \Magento\Framework\View\Element\Template
{
    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
           parent::__construct($context, $data);
    }
    
    /**
     * Disable block output when integration mode is other than iframe
     */
    
    protected function _beforeToHtml()
    {
        return parent::_beforeToHtml();
    }

    /**
     * Set default template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('Sapient_Worldpay::checkout/hpp/iframe.phtml');
    }
}
