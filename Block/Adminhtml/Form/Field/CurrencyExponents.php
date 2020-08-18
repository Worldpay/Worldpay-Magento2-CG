<?php
/**
 * @copyright 2020 Sapient
 */
namespace Sapient\Worldpay\Block\Adminhtml\Form\Field;

class CurrencyExponents extends \Sapient\Worldpay\Block\Form\Field\FieldArray\CurrencyExponentsArray
{
    /**
     * Prepare to render.
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'currency_code',
            ['label' => __('Currency Code'),'style' => 'width:60px','class' => 'required-entry']
        );
        $this->addColumn('currency', ['label' => __('Currency'),'style' => 'width:150px','class' => 'required-entry']);
        $this->addColumn('exponent', ['label' => __('Exponent'),'style' => 'width:40px','class' => 'required-entry']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
