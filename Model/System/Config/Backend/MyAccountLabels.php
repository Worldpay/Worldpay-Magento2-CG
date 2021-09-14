<?php

/**
 * @copyright 2020 Sapient
 */

namespace Sapient\Worldpay\Model\System\Config\Backend;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Phrase;

/**
 * Backend for serialized array data
 */
class MyAccountLabels extends \Magento\Framework\App\Config\Value
{

    private $generalexception;
    private $storeManager;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Sapient\Worldpay\Helper\MyAccountLabels $myAccountLabels
     * @param \Sapient\Worldpay\Helper\GeneralException $generalexception
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Sapient\Worldpay\Helper\MyAccountLabels $myAccountLabels,
        \Sapient\Worldpay\Helper\GeneralException $generalexception,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->myAccountLabels = $myAccountLabels;
        $this->generalexception = $generalexception;
        $this->storeManager = $storeManager;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Process data after load
     *
     * @return void
     */
    protected function _afterLoad()
    {
        $value = $this->getValue();
        $value = $this->myAccountLabels->makeArrayFieldValue($value);
        $this->setValue($value);
    }

    /**
     * Prepare data before save
     *
     * @return void
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        $scopeid = $this->getScopeId();
        $store = $this->storeManager->getStore($scopeid)->getCode();
        $resultArray = [];
        foreach ($value as $rowId => $row) {
            if (is_array($row)) {
                $caseSensitiveVal = trim($row['wpay_label_code']);
                $caseSensVal = strtoupper($caseSensitiveVal);
                if (array_key_exists($caseSensVal, $resultArray)) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __(sprintf($this->generalexception->getConfigValue('ACAM12', $store), $row['wpay_label_code']))
                    );
                }
                if (ctype_space($row['wpay_custom_label'])) {
                    $msg = new Phrase($this->generalexception->
                                    getConfigValue('ACAM13', $store) . "-" . $row['wpay_label_code']);
                    throw new CouldNotSaveException($msg);
                }
                $resultArray[$caseSensVal] = ['wpay_label_code' => $caseSensVal,
                    'wpay_label_desc' => $row['wpay_label_desc'],
                    'wpay_custom_label' => $row['wpay_custom_label']];
            }
        }
        $check = $this->myAccountLabels->makeStorableArrayFieldValue($resultArray);
        $this->setValue($check);
    }
}
