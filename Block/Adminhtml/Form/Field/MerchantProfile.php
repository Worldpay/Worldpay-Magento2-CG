<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Block\Adminhtml\Form\Field;

class MerchantProfile extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var \Sapient\Worldpay\Block\Adminhtml\Form\Field\Paymentmethod
     */
    protected $_paymentMethod;

    /**
     * Get activation options.
     *
     * @return \Sapient\Worldpay\Block\Adminhtml\Form\Field\Activation
     */
    protected function _getpaymentMethodRenderer()
    {
        if (!$this->_paymentMethod) {
            $this->_paymentMethod = $this->getLayout()->createBlock(
                '\Sapient\Worldpay\Block\Adminhtml\Form\Field\Paymentmethod',
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
             $this->_paymentMethod->setClass('required-entry');
        }

        return $this->_paymentMethod;
   }

    /**
     * Prepare to render.
     *
     * @return void
     */
    protected function _prepareToRender()
    {   
        $this->addColumn(
            'worldpay_payment_method',
            [
                'label' => __('Payment Method'),
                'class' => 'required-entry',
                'renderer' => $this->_getpaymentMethodRenderer()
            ]
        );

        $this->addColumn('merchant_code', ['label' => __('Merchant Code'),'style' => 'width:120px','class' => 'required-entry']);
        $this->addColumn('merchant_username', ['label' => __('Merchant Username'),'style' => 'width:120px','class' => 'required-entry']);
        $this->addColumn('merchant_password', ['label' => __('Merchant Password'), 
            'renderer' => $this->getLayout()->createBlock(
                '\Sapient\Worldpay\Block\Adminhtml\Form\Field\Password'
            )
            ]); 
        
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Prepare existing row data object.
     *
     * @param \Magento\Framework\DataObject $row
     * @return void
     */
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $options = [];
        $customAttribute = $row->getData('worldpay_payment_method');
        $key = 'option_' . $this->_getpaymentMethodRenderer()->calcOptionHash($customAttribute);
        $options[$key] = 'selected="selected"';
        $row->setData('option_extra_attrs', $options);
    }
}