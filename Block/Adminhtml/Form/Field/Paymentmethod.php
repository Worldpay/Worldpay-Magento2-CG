<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Block\Adminhtml\Form\Field;

class Paymentmethod extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * @var \Sapient\Worldpay\Model\Utilities\PaymentMethods
     */
    public $paymentutils;
    /**
     * Paymentmethod constructor.
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Sapient\Worldpay\Model\Utilities\PaymentMethods $paymentutils
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Sapient\Worldpay\Model\Utilities\PaymentMethods $paymentutils,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->paymentutils = $paymentutils;
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
        $paymentTypes = $this->paymentutils->getAvailableMethods();
        foreach ($paymentTypes as $methods) {
            foreach ($methods->types->children() as $m) {
                $result[$m->getName()]=__($m->title);
            }
        }

        return $result;
    }
}
