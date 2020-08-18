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
class CreditCardException extends \Magento\Framework\App\Config\Value
{
    
    private $helper;
    
    private $storeManager;
    
    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Sapient\Worldpay\Helper\MyAccountException $myaccountexception
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Sapient\Worldpay\Helper\CreditCardException $creditcardexception,
        \Sapient\Worldpay\Helper\GeneralException $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->creditcardexception = $creditcardexception;
        $this->helper = $helper;
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
        $value = $this->creditcardexception->makeArrayFieldValue($value);
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
        $scopeid=$this->getScopeId();
        $store = $this->storeManager->getStore($scopeid)->getCode();
        $resultArray = [];
        foreach ($value as $rowId => $row) {
            if (is_array($row)) {
                $caseSensitiveVal = trim($row['exception_code']);
                $caseSensVal  = strtoupper($caseSensitiveVal);
                if (array_key_exists($caseSensVal, $resultArray)) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __(sprintf($this->helper->getConfigValue('ACAM12', $store), $row['exception_code']))
                    );
                }
                if (ctype_space($row['exception_module_messages'])) {
                    $msg=new Phrase($this->helper->getConfigValue('ACAM13', $store)."-".$row['exception_code']);
                    throw new CouldNotSaveException($msg);
                }
                $resultArray[$row['exception_code']] = ['exception_code'
                    =>$row['exception_code'],'exception_messages'=>$row['exception_messages'],
                    'exception_module_messages'=>$row['exception_module_messages']];
            }
        }
        $value = $this->creditcardexception->makeStorableArrayFieldValue($value);
        $this->setValue($value);
    }
}
