<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Block\Adminhtml\Form\Field;

class MyAccountException extends \Sapient\Worldpay\Block\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * Prepare to render.
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'exception_code',
            ['label' => __('Message Code'),'style' => 'width:80px','class' => 'required-entry']
        );
        $this->addColumn(
            'exception_messages',
            ['label' => __('Actual Message'),'style' => 'width:300px','class' => 'required-entry']
        );
        $this->addColumn('exception_module_messages', ['label' => __('Custom Message'),'style' => 'width:200px']);
    
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
