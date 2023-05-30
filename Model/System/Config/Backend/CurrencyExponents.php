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
class CurrencyExponents extends \Magento\Framework\App\Config\Value
{
    /**
     * Worldpay helper
     *
     * @var \Magento\Catalog\Helper\Data
     */
    private $helper;
    /**
     * Store manager interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

     /**
      *
      * @var \Sapient\Worldpay\Helper\Currencyexponents
      */
    private $currencyexponent;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Sapient\Worldpay\Helper\Currencyexponents $currencyexponent
     * @param \Sapient\Worldpay\Helper\GeneralException $helper
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
        \Sapient\Worldpay\Helper\Currencyexponents $currencyexponent,
        \Sapient\Worldpay\Helper\GeneralException $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->currencyexponent = $currencyexponent;
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
        $value = $this->currencyexponent->makeArrayFieldValue($value);
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
                $caseSensitiveVal = trim($row['currency_code']);
                $caseSensVal  = strtoupper($caseSensitiveVal);

                if (array_key_exists($caseSensVal, $resultArray)) {
                    $errorMsg = $this->helper->getConfigValue('ACAM12', $store);
                    throw new CouldNotSaveException(__(sprintf($errorMsg, $row['currency_code'])));
                }
                if (ctype_space($row['exponent'])) {
                    $msg=new Phrase($this->helper->getConfigValue('ACAM13', $store)."-".$row['exponent']);
                    throw new CouldNotSaveException($msg);
                }
                $resultArray[$row['currency_code']] = ['currency_code'=>$row['currency_code'],
                    'currency'=>$row['currency'], 'exponent'=>$row['exponent']];
            }
        }
        $value = $this->currencyexponent->makeStorableArrayFieldValue($value);
        $this->setValue($value);
    }
}
