<?php
/**
 * @copyright 2020 Sapient
 */
namespace Sapient\Worldpay\Model\System\Config\Backend;

use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Backend for serialized array data
 */
class ExtendedResponseCodes extends \Magento\Framework\App\Config\Value
{
    
    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Sapient\Worldpay\Helper\ExtendedResponseCodes $extendedResponseCodes
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Sapient\Worldpay\Helper\ExtendedResponseCodes $extendedResponseCodes,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->extendedResponseCodes = $extendedResponseCodes;
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
        $value = $this->extendedResponseCodes->makeArrayFieldValue($value);
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
        $resultArray = [];
        foreach ($value as $rowId => $row) {
            if (is_array($row)) {
                if (array_key_exists($row['wpay_code'], $resultArray)) {
                    $errorMsg = 'Error Code %s already exist!';
                    throw new CouldNotSaveException(__(sprintf($errorMsg, $row['wpay_code'])));
                }
                $resultArray[$row['wpay_code']] = ['wpay_desc'=>$row['wpay_desc'], 'custom_msg'=>$row['custom_msg']];
            }
        }
        $value = $this->extendedResponseCodes->makeStorableArrayFieldValue($value);
        $this->setValue($value);
    }
}
