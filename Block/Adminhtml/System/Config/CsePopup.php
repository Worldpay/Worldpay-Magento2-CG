<?php
/**
 * Copyright © Sapient, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Sapient\Worldpay\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class CsePopup extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Sapient_Worldpay::system/config/csepopup.phtml';

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }
}
?>