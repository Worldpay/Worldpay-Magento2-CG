<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sapient\Worldpay\Block\Adminhtml\Form\Field;

/**
 * Class Password which helps to set WP merchant handlings
 */
class Password extends \Magento\Framework\View\Element\AbstractBlock
{
    /**
     * Update password
     *
     * @param string $value
     * @return string
     */

    protected function _toHtml()
    {

        $html = '<input type="password" style="width:120px;" name="'.$this->getName().'" id="'.$this->getId().'"';
        $html .= 'value="' . $this->escapeHtml($this->getValue()) . '" ';
        $html .= 'class="required-entry ' . $this->getClass() . '" ' . $this->getExtraParams() . '/> ';
        return $html;
    }
     /**
      * Set Input Name
      *
      * @param string $value
      * @return Sapient\Worldpay\Block\Adminhtml\Form\Field\MerchantProfile
      */

    public function setInputName($value)
    {
        return $this->setName($value);
    }
    /**
     * Set Input Value
     *
     * @param string $value
     * @return string
     */

    public function setInputId($value)
    {
        return $this->setId($value);
    }
    /**
     * Return Html
     *
     * @return string
     */

    public function getHtml()
    {
        return $this->toHtml();
    }
}
