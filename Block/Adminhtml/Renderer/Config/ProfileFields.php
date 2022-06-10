<?php
/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Block\Adminhtml\Renderer\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ProfileFields extends Field
{
    
    /**
     * Create new admin config fields
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setStyle('width:110px;')->setName($element->getName() . '[]');
        if ($element->getValue()) {
            $values = explode(',', $element->getValue());
        } else {
            $values = [];
        }
        $merchantcode = $element->setValue(isset($values[0]) ? $values[0] : null)->getElementHtml();
        $xmlusername = $element->setValue(isset($values[1]) ? $values[1] : null)->getElementHtml();
        $xmlpassword = $element->setType('password')->setValue(isset($values[2]) ? $values[2] : null)->getElementHtml();
        return $merchantcode ." ". $xmlusername ." ". $xmlpassword;
    }
}
