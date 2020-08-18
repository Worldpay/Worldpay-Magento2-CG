<?php
/**
 * @copyright 2020 Sapient
 */

namespace Sapient\Worldpay\Block\Form\Field\FieldArray;

use \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Backend system config array field renderer
 *
 */
abstract class CurrencyExponentsArray extends AbstractFieldArray
{
    /**
     * @var string
     */
    protected $_template = 'Sapient_Worldpay::form/field/currencyexponents.phtml';

    /**
     * Check if columns are defined, set template
     *
     * @return void
     */
    protected function _construct()
    {
        if (!$this->_addButtonLabel) {
            $this->_addButtonLabel = __('Add');
        }
        parent::_construct();
    }

    /**
     * Render array cell for prototypeJS template
     *
     * @param string $columnName
     * @return string
     * @throws \Exception
     */
    public function renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new \Magento\Framework\Exception\LocalizedException('Wrong column name specified.');
        }
        $column = $this->_columns[$columnName];
        $inputName = $this->_getCellInputElementName($columnName);

        if ($column['renderer']) {
            return $column['renderer']->setInputName(
                $inputName
            )->setInputId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setColumnName(
                $columnName
            )->setColumn(
                $column
            )->toHtml();
        }

        return '<input type="text" id="' . $this->_getCellInputElementId(
            '<%- _id %>',
            $columnName
        ) .
            '"' .
            ' name="' .
            $inputName .
            '" value="<%- ' .
            $columnName .
            ' %>" ' .
            ($column['size'] ? 'size="' .
            $column['size'] .
            '"' : '') .
            ' class="' .
            (isset($column['class'])
                ? $column['class']
                : 'input-text') . '"' . (isset($column['style']) ? ' style="' . $column['style'] . '"' : '') . '/>';
    }
}
