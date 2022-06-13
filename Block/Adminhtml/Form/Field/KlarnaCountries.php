<?php
/**
 * @copyright 2020 Sapient
 */
namespace Sapient\Worldpay\Block\Adminhtml\Form\Field;

class KlarnaCountries extends \Sapient\Worldpay\Block\Form\Field\FieldArray\KlarnaSubscriptionArray
{
    /**
     * @var \Sapient\Worldpay\Block\Adminhtml\Form\Field\KlarnaCountriesList
     */
    protected $_klarnaCountriesList;

    /**
     * Get activation options.
     *
     * @return \Sapient\Worldpay\Block\Adminhtml\Form\Field\Activation
     */

    protected function _getKlarnaCountriesRenderer()
    {
        if (!$this->_klarnaCountriesList) {
            $this->_klarnaCountriesList = $this->getLayout()->createBlock(
                \Sapient\Worldpay\Block\Adminhtml\Form\Field\KlarnaCountriesList::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            
            $this->_klarnaCountriesList->setClass('required-entry');
        }

        return $this->_klarnaCountriesList;
    }

    /**
     * Prepare to render.
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'worldpay_klarna_subscription',
            [
                'label' => __('Country'),
                'style' => 'width:200px !important',
                'class' => 'required-entry',
                'renderer' => $this->_getKlarnaCountriesRenderer()
            ]
        );

        $this->addColumn(
            'subscription_days',
            ['label' => __('Subscription Days'),'style' => 'width:100px','class' => 'required-entry']
        );
        
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
        $customAttribute = $row->getData('worldpay_klarna_subscription');
        $key = 'option_' . $this->_getKlarnaCountriesRenderer()->calcOptionHash($customAttribute);
        $options[$key] = 'selected="selected"';
        $row->setData('option_extra_attrs', $options);
    }
}
