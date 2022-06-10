<?php
/**
 * @copyright 2020 Sapient
 */
namespace Sapient\Worldpay\Block\Adminhtml\Form\Field;

class Instalmenttype extends \Magento\Framework\View\Element\Html\Select
{
    
    /**
     * Instalmenttype constructor.
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Sapient\Worldpay\Model\Config\Source\InstalmentCountryType $instalmentutils
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Sapient\Worldpay\Model\Config\Source\InstalmentCountryType $instalmentutils,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->instalmentutils = $instalmentutils;
    }

    /**
     * Set input name
     *
     * @param string $value
     * @return Sapient\Worldpay\Block\Adminhtml\Form\Field\InstalmentConfig
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Parse to html.
     *
     * @return mixed
     */
    public function _toHtml()
    {
       
        if (!$this->getOptions()) {
            $this->setOptions($this->instalmentutils->toOptionArray());
        }
        return parent::_toHtml();
    }
}
