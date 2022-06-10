<?php
declare(strict_types=1);
/**
 * Copyright © 2020 Sapient.
 */
namespace Sapient\Worldpay\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Sapient\Worldpay\Model\Config\Source\InstalmentCountries;

class CountryColumn extends Select
{
    /**
     * @var InstalmentCountries $instalmentcountries
     */
    private $instalmentcountries;
     
    /**
     * CountryColumn constructor
     *
     * @param string $context
     * @param string $instalmentcountries
     * @param array $data
     */
    public function __construct(
        Context $context,
        InstalmentCountries $instalmentcountries,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->instalmentcountries = $instalmentcountries;
    }

    /**
     * Set Input Name
     *
     * @param string $value
     * @return Sapient\Worldpay\Block\Adminhtml\Form\Field\InstalmentCountries
     */
    public function setInputName($value)
    {
        return $this->setName($value . '[]');
    }

    /**
     * Display Html
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->instalmentcountries->toOptionArray());
        }
        $this->setExtraParams('multiple="multiple"');
        return parent::_toHtml();
    }
}
