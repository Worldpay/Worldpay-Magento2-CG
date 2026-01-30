<?php


namespace Sapient\Worldpay\Model\System\Config\Backend;

/**
 * Store data for PluginUpgradeDates
 *
 */
class PluginUpgradeDates extends \Magento\Framework\App\Config\Value
{
    /* Module Name */
    public const MODULE_NAME = 'Sapient_Worldpay';
     /**
      *
      * @var \Sapient\Worldpay\Logger\WorldpayLogger
      */
    private $wplogger;

     /**
      * Store manager interface
      *
      * @var \Magento\Store\Model\StoreManagerInterface
      */
    private $storeManager;

     /**
      * @var \Magento\Framework\App\Config\ScopeConfigInterface
      */
    private $scopeConfig;

     /**
      * @var \Magento\Framework\App\Config\Storage\WriterInterface
      */
    private $configWriter;

    /**
     * @var \Magento\Framework\App\Cache\Manager
     */
    private $cacheManager;

    /**
     * @var \Sapient\Worldpay\Model\System\Config\Backend\PluginVersionHistory
     */
    private $versionhistory;

    /**
     * @var \Sapient\Worldpay\Model\System\Config\Backend\CurrentPluginVersion
     */
    private $currentversionconfig;
    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Sapient\Worldpay\Model\System\Config\Backend\CurrentPluginVersion $currentversionconfig
     * @param \Magento\Framework\App\Cache\Manager $cacheManager
     * @param \Sapient\Worldpay\Model\System\Config\Backend\PluginVersionHistory $versionhistory
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Sapient\Worldpay\Model\System\Config\Backend\CurrentPluginVersion $currentversionconfig,
        \Magento\Framework\App\Cache\Manager $cacheManager,
        \Sapient\Worldpay\Model\System\Config\Backend\PluginVersionHistory $versionhistory,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->wplogger = $wplogger;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->currentversionconfig = $currentversionconfig;
        $this->cacheManager = $cacheManager;
        $this->versionhistory = $versionhistory;
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
        $value = $this->getUpgradeDates();
        if ((isset($value['oldData']) && !empty($value['oldData'])) &&
                (isset($value['newData']) && !empty($value['newData']))) {
            if ($value['is_new_version']) {
                $data = $value['oldData'].",".$value['newData'];
            } else {
                $data = $value['oldData'];
            }
            $data =(array_unique(explode(",", $data)));
            $data = array_slice($data, -3, 3, true);
            $data = implode(",", $data);
            $this->setValue($data);
            $this->configWriter->save(
                'worldpay/general_config/plugin_tracker/upgrade_dates',
                $data
            );
            $this->cacheManager->flush($this->cacheManager->getAvailableTypes());

        } elseif ((isset($value['newData']) && !empty($value['newData']))) {
            $this->setValue($value['newData']);
            $this->configWriter->save(
                'worldpay/general_config/plugin_tracker/upgrade_dates',
                $value['newData']
            );
            $this->cacheManager->flush($this->cacheManager->getAvailableTypes());

        }
    }
    
    /**
     * Prepare data before save
     *
     * @return void
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        $value = $this->getUpgradeDates();
        if ((isset($value['oldData']) && !empty($value['oldData'])) &&
                (isset($value['newData']) && !empty($value['newData']))) {
            if ($value['is_new_version']) {
                $data = $value['oldData'].",".$value['newData'];
            } else {
                $data = $value['oldData'];
            }
            $data =(array_unique(explode(",", $data)));
            $data = array_slice($data, -3, 3, true);
            $data = implode(",", $data);
            $this->setValue($data);
            $this->configWriter->save(
                'worldpay/general_config/plugin_tracker/upgrade_dates',
                $data
            );
        } elseif ((isset($value['newData']) && !empty($value['newData']))) {
            $this->setValue($value['newData']);
            $this->configWriter->save(
                'worldpay/general_config/plugin_tracker/upgrade_dates',
                $value['newData']
            );
        }
    }
    /**
     * Get Upgrade Dates
     *
     * @return array
     */
    public function getUpgradeDates()
    {
        $value=[];
        //current version
        $currentPluginData = $this->scopeConfig->getValue(
            'worldpay/general_config/plugin_tracker/current_wopay_plugin_version',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        //plugin upgrade dates
        $currentHistoryData = $this->scopeConfig->getValue(
            'worldpay/general_config/plugin_tracker/upgrade_dates',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        //versions used till date
        $currentVersionHistoryData = $this->scopeConfig->getValue(
            'worldpay/general_config/plugin_tracker/wopay_plugin_version_history',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (isset($currentHistoryData) && !empty($currentHistoryData)) {
            $value['oldData'] = $currentHistoryData;
        }
        //check new version
        $currentVersion['newVersion'] = $this->currentversionconfig->getModuleVersion(self::MODULE_NAME);
        if ($currentPluginData &&
                (isset($currentVersion['newVersion']) && $currentVersion['newVersion'] == $currentPluginData)) {
            $value['newVersion'] = $currentPluginData;
        } else {
            $value['newVersion'] = isset($currentVersion['newVersion'])?$currentVersion['newVersion']:"";
        }
        $currentVersionHistoryDataAry = explode(',', (string) $currentVersionHistoryData);
        $value['is_new_version'] = false;
        if (!in_array($value['newVersion'], $currentVersionHistoryDataAry)) {
            $value['is_new_version'] = true;
        }
        
        $pastversions =  $this->getVersionHistoryDetails()!=null? $this->getVersionHistoryDetails()
                :$currentVersionHistoryData;
        if (isset($pastversions)) {
            $versionHistoryData = explode(',', $pastversions);
        }
        
        if (empty($currentHistoryData)) {
            $value['newData'] = date("d-m-Y").' - ('.$pastversions.')';
            ;
            return $value;
        } else {
            $datesHistoryData = explode(',', $currentHistoryData);
        }
        if (empty($currentPluginData) ||
                (!empty($currentPluginData) && empty($currentVersionHistoryData)
                && (empty($value['oldData']))) || (count($versionHistoryData) != count($datesHistoryData))
                ) {
                    
            if (count($versionHistoryData) >= 2) {
                $recentPluginVersion = array_slice($versionHistoryData, -2, 2, false);
                $data = date("d-m-Y").' - ('.$recentPluginVersion[0].' to '.$recentPluginVersion[1].')';

            } else {
                $data = date("d-m-Y").' ('.$versionHistoryData[0].')';
            }
            $value['newData'] = $data;
            if (isset($value['oldData']) && ($value['oldData'] == $value['newData'])) {
                $value['oldData'] = "";
            }
            if (preg_match('/,/', $currentPluginData)) {
                $currentPluginData= substr($currentPluginData, 0, strpos($currentPluginData, ','));
                $this->configWriter->save(
                    'worldpay/general_config/plugin_tracker/current_wopay_plugin_version',
                    $currentPluginData
                );
            }
            return array_unique($value);
        }
        
        return array_unique($value);
    }
    /**
     * Get Version History Details
     *
     * @return array
     */
    public function getVersionHistoryDetails()
    {
        $value = null;
        $versionDetails = $this->versionhistory->getPluginVersionHistoryDetails();
        if (isset($versionDetails['oldData']) && !empty($versionDetails['oldData'])
                && (isset($versionDetails['newData']) && !empty($versionDetails['newData']))) {
            $data = $versionDetails['oldData'].",".$versionDetails['newData'];
            $data =(array_unique(explode(",", $data)));
            $data = implode(",", $data);
            $value = $data;
        } elseif (isset($versionDetails['newData'])) {
            $value = $versionDetails['newData'];
        }
        return $value;
    }
}
