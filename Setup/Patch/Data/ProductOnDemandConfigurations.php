<?php
/**
 * Copyright © Sapient, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sapient\Worldpay\Setup\Patch\Data;

use Magento\Config\Model\Config\Factory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Serialize\SerializerInterface;

class ProductOnDemandConfigurations implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;
    /**
     * @var CategorySetupFactory $categorySetupFactory
     */
    protected $categorySetupFactory;
     /**
      * @var Factory $configFactory
      */
    protected $configFactory;
    /**
     * @var SerializerInterface $serializer
     */
    protected $serializer;

    /**
     * DefaultConfigurations constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CategorySetupFactory $categorySetupFactory
     * @param Factory $configFactory
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CategorySetupFactory $categorySetupFactory,
        Factory $configFactory,
        SerializerInterface $serializer
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->categorySetupFactory = $categorySetupFactory;
        $this->configFactory = $configFactory;
        $this->serializer = $serializer;
    }
    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply(): void
    {
        $catalogSetup = $this->categorySetupFactory->create(['setup' => $this->moduleDataSetup]);

        $groupName = 'Product on Demand';
        $catalogSetup->addAttributeGroup(Product::ENTITY, 'Default', $groupName, 16);

        $catalogSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'product_on_demand',
            [
                'group' => $groupName,
                'type' => 'int',
                'frontend' => '',
                'label' => 'Enabled',
                'input' => 'boolean',
                'class' => '',
                'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::VALUE_NO,
                'apply_to' => implode(',', [Type::TYPE_SIMPLE, Type::TYPE_VIRTUAL]),
                'visible_on_front' => false,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => true,
                'used_in_product_listing' => true
            ]
        );
        // Default Labels and configurations
        $this->saveCCLabels();
        $this->saveMyAccountAlertsCodes();
    }
    /**
     * Convert array to string
     *
     * @param array $exceptionValues
     */
    public function convertArrayToString($exceptionValues): bool|string
    {
        $resultArray = [];
        foreach ($exceptionValues as $row) {
            $payment_type = $row['exception_code'];
            $rs['exception_messages'] = $row['exception_messages'];
            $rs['exception_module_messages'] = $row['exception_module_messages'];
            $resultArray[$payment_type] = $rs;
        }
        return $this->serializer->serialize($resultArray);
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    public function saveCCLabels(): void
    {
        $index = time();
        $newExceptionValues = [
            'CCAM30' => [
                "exception_messages" => "Save the card before placing the order",
                "exception_module_messages" => ""
            ],
        ];

        // Retrieve existing data
        $existingData = $this
            ->configFactory
            ->create()
            ->getConfigDataValue('worldpay_exceptions/ccexceptions/cc_exception');
        $existingValues = $existingData ? $this->serializer->unserialize($existingData) : [];

        // Merge new data with existing data
        $mergedValues = array_merge($existingValues, $newExceptionValues);

        $exceptionCodes = $this->serializer->serialize($mergedValues);
        $configData = [
            'section' => 'worldpay_exceptions',
            'website' => null,
            'store'   => null,
            'groups'  => [
                'ccexceptions' => [
                    'fields' => [
                        'cc_exception' => [
                            'value' => $exceptionCodes
                            ],
                    ],
                ],
            ],
        ];

        $configModel = $this->configFactory->create(['data' => $configData]);
        $configModel->save();
    }

    public function saveMyAccountAlertsCodes(): void
    {
        $index = time();
        $newExceptionValues = [
            $index . '_23' => ["exception_code" => "MCAM23",
                "exception_messages" => "Selected product on demand is not available.",
                "exception_module_messages" => ""],
        ];

        // Retrieve existing data
        $existingData = $this
            ->configFactory
            ->create()
            ->getConfigDataValue('worldpay_exceptions/my_account_alert_codes/response_codes');
        $existingValues = $existingData ? $this->serializer->unserialize($existingData) : [];

        // Merge new data with existing data
        $mergedValues = array_merge($existingValues, $newExceptionValues);

        $exceptionCodes = $this->serializer->serialize($mergedValues);
        $configData = [
            'section' => 'worldpay_exceptions',
            'website' => null,
            'store'   => null,
            'groups'  => [
                'my_account_alert_codes' => [
                    'fields' => [
                        'response_codes' => [
                            'value' => $exceptionCodes
                        ],
                    ],
                ],
            ],
        ];
        /** @var \Magento\Config\Model\Config $configModel */
        $configModel = $this->configFactory->create(['data' => $configData]);
        $configModel->save();
    }
}
