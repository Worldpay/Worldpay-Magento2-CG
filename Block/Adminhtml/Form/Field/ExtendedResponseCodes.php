<?php
/**
 * @copyright 2020 Sapient
 */
namespace Sapient\Worldpay\Block\Adminhtml\Form\Field;

class ExtendedResponseCodes extends \Sapient\Worldpay\Block\Form\Field\FieldArray\ExtendedResponseCodesArray
{

    /**
     * Prepare to render.
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'wpay_code',
            ['label' => __('Error Code'),'style' => 'width:100px','class' => 'required-entry']
        );
        $this->addColumn(
            'wpay_desc',
            ['label' => __('Worldpay Response'),'style' => 'width:200px','class' => 'required-entry']
        );
        $this->addColumn('custom_msg', ['label' => __('Custom Response'),'style' => 'width:200px']);
        
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
        $row->setData('option_extra_attrs', $options);
    }
}
