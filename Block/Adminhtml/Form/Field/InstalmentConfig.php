<?php
/**
 * @copyright 2020 Sapient
 */
namespace Sapient\Worldpay\Block\Adminhtml\Form\Field;

class InstalmentConfig extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var \Sapient\Worldpay\Block\Adminhtml\Form\Field\Instalmenttype
     */
    protected $_instalmenttype;
    
    /**
     * @var countryRenderer
     */
    private $countryRenderer;

    /**
     * Get activation options.
     *
     * @return \Sapient\Worldpay\Block\Adminhtml\Form\Field\Activation
     */
    protected function _getinstalmentRenderer()
    {
        if (!$this->_instalmenttype) {
            $this->_instalmenttype = $this->getLayout()->createBlock(
                \Sapient\Worldpay\Block\Adminhtml\Form\Field\Instalmenttype::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
             $this->_instalmenttype->setClass('required-entry');
        }

        return $this->_instalmenttype;
    }

    /**
     * Prepare to render.
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'worldpay_instalment_type',
            [
                'label' => __('Instalment Type'),
                'style' => 'width:200px !important',
                'class' => 'required-entry',
                'renderer' => $this->_getinstalmentRenderer()
            ]
        );

//        $this->addColumn('instalment_country', ['label' => __('Countries'),
//            'renderer' => $this->getLayout()->createBlock(
//                '\Sapient\Worldpay\Block\Adminhtml\Form\Field\InstalmentCountries'
//            )
//            ]);
//
//        $this->_addAfter = false;
//        $this->_addButtonLabel = __('Add');
        /* Multiselect code */
        $this->addColumn('country', [
            'label' => __('Country'),
            'renderer' => $this->getCountryRenderer(),
            'class' => 'required-entry',
            'extra_params' => 'multiple="multiple"'
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
        $customAttribute = $row->getData('worldpay_instalment_type');
        if ($customAttribute !== null) {
            $key = 'option_' . $this->_getinstalmentRenderer()->calcOptionHash($customAttribute);
            $options[$key] = 'selected="selected"';
        }
         $countries = $row->getCountry();
        if (count($countries) > 0) {
            foreach ($countries as $country) {
                $options['option_' . $this->getCountryRenderer()->calcOptionHash($country)]
                    = 'selected="selected"';
            }
        }
        $row->setData('option_extra_attrs', $options);
    }
    
    /* Multiselect renderer for countries */
    private function getCountryRenderer()
    {
        if (!$this->countryRenderer) {
            $this->countryRenderer = $this->getLayout()->createBlock(
                \Sapient\Worldpay\Block\Adminhtml\Form\Field\CountryColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
             $this->countryRenderer->setClass('required-entry');
        }
        return $this->countryRenderer;
    }
}
