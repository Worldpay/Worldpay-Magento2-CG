<?php

/**
 * @copyright 2020 Sapient
 */

namespace Sapient\Worldpay\Block\Adminhtml\Form\Field;

class AdminLabels extends \Sapient\Worldpay\Block\Form\Field\FieldArray\CustomLabelsArray
{

    /**
     * Prepare to render.
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'wpay_label_code',
            ['label' => __('Label Code'),
            'style' => 'width:100px',
            'class' => 'required-entry']
        );
        $this->addColumn(
            'wpay_label_desc',
            ['label' => __('Actual Label'),
            'style' => 'width:200px',
            'class' => 'required-entry']
        );
        $this->addColumn('wpay_custom_label', ['label' => __('Custom label'),
            'style' => 'width:200px']);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
