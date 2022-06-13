<?php
/**
 * @copyright 2020 Sapient
 */

namespace Sapient\Worldpay\Block\Adminhtml\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Disable extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Get getElementHtml
     *
     * @param string $element
     * @return string
     */

    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setData('readonly', 1);
        return $element->getElementHtml();
    }
}
