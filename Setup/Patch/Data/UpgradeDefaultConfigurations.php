<?php
/**
 * Copyright &copy; Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 namespace Sapient\Worldpay\Setup\Patch\Data;

 use Magento\Framework\Setup\Patch\DataPatchInterface;
 use Magento\Framework\Setup\ModuleDataSetupInterface;
 use Magento\Framework\App\Config\ScopeConfigInterface;
 use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Serialize\SerializerInterface;

class UpgradeDefaultConfigurations implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var SerializerInterface $serializer
     */
    protected $serializer;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param WriterInterface $configWriter
     * @param ScopeConfigInterface $scopeConfig
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        WriterInterface $configWriter,
        ScopeConfigInterface $scopeConfig,
        SerializerInterface $serializer
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
    }
    /**
     * Update existing data in the database.
     *
     * @param ModuleDataSetupInterface $setup
     * @return void
     */

    private function updateExistingData()
    {
        $existingValue = $this->scopeConfig->getValue(
            'worldpay_exceptions/ccexceptions/cc_exception',
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
        $updatedValue = $this->modifyConfigValue($existingValue);
        $this->configWriter->save('worldpay_exceptions/ccexceptions/cc_exception', $updatedValue);
    }
    
    /**
     * Modify the config value as per the requirements
     *
     * @param string $existingValue
     * @return string
     */
    private function modifyConfigValue($existingValue)
    {
        $exceptionMsg = "You already seem to have this card number "
        . "stored, If your card details have changed, you can update them via"
        . " My Account -> Saved Card";

        $newValue = $this->serializer->unserialize($existingValue);
        $newValue['CCAM22']['exception_messages'] = $exceptionMsg;

        return $this->serializer->serialize($newValue);
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $this->updateExistingData();
        $this->moduleDataSetup->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function revert()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
