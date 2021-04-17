<?php
/**
 * @copyright 2020 Sapient
 */
namespace Sapient\Worldpay\Block\Adminhtml\Form\Field;

class KlarnaCountriesList extends \Magento\Framework\View\Element\Html\Select
{
    
    /**
     * Paymentmethod constructor.
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Sapient\Worldpay\Model\Config\Source\KlarnaCountries $klarnaCountries
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Sapient\Worldpay\Model\Config\Source\KlarnaCountries $klarnaCountries,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->klarnaCountries = $klarnaCountries;
    }

    /**
     * @param string $value
     * @return Sapient\Worldpay\Block\Adminhtml\Form\Field\KlarnaCountries
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
       
        $paymetType= $this->getAllPaymentType() ;

        foreach ($paymetType as $paymentname => $paymentTitle) {
            $this->addOption($paymentname, $paymentTitle);
        }

        return parent::_toHtml();
    }

    /**
     * Retrive all the payment type.
     *
     * @return mixed
     */
    private function getAllPaymentType()
    {

        $result= [];
        $result['']=__('Select');
        $paymetType= $this->klarnaCountries->toOptionArray();
        foreach ($paymetType as $methods) {
            $result[$methods['value']]= $methods['label'];
        }

        return $result;
    }
}
