<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sapient\Worldpay\Block;

/**
 * Multishipping cart link
 *
 * @api
 * @since 100.0.2
 */
class MultishippingCheckoutLink extends \Magento\Multishipping\Block\Checkout\Link
{
      /**
       * @var \Sapient\Worldpay\Helper\Recurring
       */
    protected $_sapientHelper;
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Multishipping\Helper\Data $helper
     * @param \Sapient\Worldpay\Helper\Recurring $sapientHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Multishipping\Helper\Data $helper,
        \Sapient\Worldpay\Helper\Recurring $sapientHelper,
        array $data = []
    ) {
        $this->_sapientHelper = $sapientHelper;
        parent::__construct($context, $helper, $data);
    }

     /**
      * Render Quote information and return result html
      *
      * @return string
      */
      
    public function _toHtml ()
    {
        if (!$this->helper->isMultishippingCheckoutAvailable()) {
            return '';
        }
        if ($this->_sapientHelper->quoteContainsSubscription($this->getQuote())) {
            return '';
        }
        return parent::_toHtml();
    }
}
