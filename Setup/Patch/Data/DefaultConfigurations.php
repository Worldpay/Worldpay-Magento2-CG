<?php
/**
 * Copyright © Sapient, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sapient\Worldpay\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Serialize\SerializerInterface;

class DefaultConfigurations implements DataPatchInterface
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
      * @var \Magento\Config\Model\Config\Factory $configFactory
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
     * @param \Magento\Config\Model\Config\Factory $configFactory
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CategorySetupFactory $categorySetupFactory,
        \Magento\Config\Model\Config\Factory $configFactory,
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
    public function apply()
    {
        $catalogSetup = $this->categorySetupFactory->create(['setup' => $this->moduleDataSetup]);
        $groupName = 'Subscriptions';
            $catalogSetup->addAttributeGroup(Product::ENTITY, 'Default', $groupName, 16);

            $catalogSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'worldpay_recurring_enabled',
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
                    'is_filterable_in_grid' => false,
                    'used_in_product_listing' => true
                ]
            );

            $catalogSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'worldpay_recurring_allow_start',
                [
                    'group' => $groupName,
                    'type' => 'int',
                    'frontend' => '',
                    'label' => 'Allow Selectable Start Date',
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
                    'is_used_in_grid' => false,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false
                ]
            );
            $groupName = 'Level23 Data Configuration';
            $catalogSetup->addAttributeGroup(Product::ENTITY, 'Default', $groupName, 16);

            $catalogSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'commodity_code',
                [
                    'group' => $groupName,
                    'type' => 'varchar',
                    'frontend' => '',
                    'label' => 'commodity code',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'apply_to' => '',
                    'visible_on_front' => false,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                    'used_in_product_listing' => true
                ]
            );

            $catalogSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'unit_of_measure',
                [
                    'group' => $groupName,
                    'type' => 'varchar',
                    'frontend' => '',
                    'label' => 'Unit of Measure',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'apply_to' => '',
                    'visible_on_front' => false,
                    'is_used_in_grid' => false,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false
                ]
            );
            // Default Labels and configurations
            $this->saveExtendedResponseCode();
            $this->saveCCLabels();
            $this->saveMyAccountAlertsCodes();
            $this->saveAdminLabelsExceptions();
            $this->saveMiscellaneous();
            $this->saveCheckoutLabels();
            $this->saveMyAccountLabels();
            $this->saveAdminLabels();
            $this->saveKlarnaConfig();
            $this->addExtraCheckoutLabels();
    }
    /**
     * Convert array to string
     *
     * @param array $exceptionValues
     */
    public function convertArrayToString($exceptionValues)
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
     * Convert array string to labels
     *
     * @param array $exceptionValues
     */
    public function convertArrayToStringForLabels($exceptionValues)
    {
        $resultArray = [];
        foreach ($exceptionValues as $row) {
            $payment_type = $row['wpay_label_code'];
            $rs['wpay_label_desc'] = $row['wpay_label_desc'];
            $rs['wpay_custom_label'] = $row['wpay_custom_label'];
            $resultArray[$payment_type] = $rs;
        }
         return $this->serializer->serialize($resultArray);
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [
            \Sapient\Worldpay\Setup\Patch\Data\UpgradeDefaultConfigurations::class
        ];
    }
    /**
     * Save Extended Response Code
     */
    public function saveExtendedResponseCode()
    {
        $index = time();
        $responseValue  = [$index.'_0' => ["wpay_code" => "0",
                                        "wpay_desc" => "Approved",
                                        "custom_msg" => ""],
                                    $index.'_1' => ["wpay_code" => "1",
                                        "wpay_desc" => "Refer to card issuer OR Referred, Call Authorisation Center",
                                        "custom_msg" => ""],
                                    $index.'_2' => ["wpay_code" => "2",
                                        "wpay_desc" => "Refer to card issuer, special condition",
                                        "custom_msg" => ""],
                                    $index.'_3' => ["wpay_code" => "3",
                                        "wpay_desc" => "Invalid merchant OR Invalid merchant or service provider",
                                        "custom_msg" => ""],
                                    $index.'_4' => ["wpay_code" => "4",
                                        "wpay_desc" => "Capture card OR Declined, Retain Card",
                                        "custom_msg" => ""],
                                    $index.'_5' => ["wpay_code" => "5",
                                        "wpay_desc" => "Do not honour OR Declined",
                                        "custom_msg" => ""],
                                    $index.'_6' => ["wpay_code" => "6",
                                        "wpay_desc" => "Error",
                                        "custom_msg" => ""],
                                    $index.'_7' => ["wpay_code" => "7",
                                        "wpay_desc" => "Pickup card, special condition",
                                        "custom_msg" => ""],
                                    $index.'_8' => ["wpay_code" => "8",
                                        "wpay_desc" => "Honour with ID OR Transaction approved with ID",
                                        "custom_msg" => ""],
                                    $index.'_9' => ["wpay_code" => "10",
                                        "wpay_desc" => "Partial Approval",
                                        "custom_msg" => ""],
                                    $index.'_10' => ["wpay_code" => "12",
                                        "wpay_desc" => "Invalid transaction", "custom_msg" => ""],
                                    $index.'_11' => ["wpay_code" => "13",
                                        "wpay_desc" => "Invalid amount OR Invalid amount "
                                        . "(currency conversion overflow)",
                                        "custom_msg" => ""],
                                    $index.'_12' => ["wpay_code" => "14",
                                        "wpay_desc" => "Invalid card number OR Invalid account number "
                                        . "(no such number)",
                                        "custom_msg" => ""],
                                    $index.'_13' => ["wpay_code" => "15",
                                        "wpay_desc" => "Invalid issuer",
                                        "custom_msg" => ""],
                                    $index.'_14' => ["wpay_code" => "19",
                                        "wpay_desc" => "Re-enter transaction",
                                        "custom_msg" => ""],
                                    $index.'_15' => ["wpay_code" => "20",
                                        "wpay_desc" => "ERROR OR No action taken (unable to back out prior)",
                                        "custom_msg" => ""],
                                    $index.'_16' => ["wpay_code" => "25",
                                        "wpay_desc" => "Unable to locate record in file, or account",
                                        "custom_msg" => ""],
                                    $index.'_17' => ["wpay_code" => "28",
                                        "wpay_desc" => "File is temporarily unavailable",
                                        "custom_msg" => ""],
                                    $index.'_18' => ["wpay_code" => "30",
                                        "wpay_desc" => "Format error OR ERROR",
                                        "custom_msg" => ""],
                                    $index.'_19' => ["wpay_code" => "34",
                                        "wpay_desc" => "FRAUD SUSPICION",
                                        "custom_msg" => ""],
                                    $index.'_20' => ["wpay_code" => "39",
                                        "wpay_desc" => "No credit account",
                                        "custom_msg" => ""],
                                    $index.'_21' => ["wpay_code" => "41",
                                        "wpay_desc" => "Lost card OR Pickup card (lost card)",
                                        "custom_msg" => ""],
                                    $index.'_22' => ["wpay_code" => "43",
                                        "wpay_desc" => "Stolen card OR Pickup card (stolen card)",
                                        "custom_msg" => ""],
                                    $index.'_23' => ["wpay_code" => "51",
                                        "wpay_desc" => "Insufficient funds OR Insufficient funds/over credit limit",
                                        "custom_msg" => ""],
                                    $index.'_24' => ["wpay_code" => "52",
                                        "wpay_desc" => "No checking account",
                                        "custom_msg" => ""],
                                    $index.'_25' => ["wpay_code" => "53",
                                        "wpay_desc" => "No savings account",
                                        "custom_msg" => ""],
                                    $index.'_26' => ["wpay_code" => "54",
                                        "wpay_desc" => "Expired card OR Declined, Expired Card",
                                        "custom_msg" => ""],
                                    $index.'_27' => ["wpay_code" => "55",
                                        "wpay_desc" => "Invalid PIN OR ERROR",
                                        "custom_msg" => ""],
                                    $index.'_28' => ["wpay_code" => "57",
                                        "wpay_desc" => "Transaction not permitted to issuer/cardholder",
                                        "custom_msg" => ""],
                                    $index.'_29' => ["wpay_code" => "58",
                                        "wpay_desc" => "Transaction not permitted to acquirer/terminal",
                                        "custom_msg" => ""],
                                    $index.'_30' => ["wpay_code" => "61",
                                        "wpay_desc" => "Exceeds withdrawal amount limit",
                                        "custom_msg" => ""],
                                    $index.'_31' => ["wpay_code" => "62",
                                        "wpay_desc" => "Restricted card OR Restricted card "
                                        . "(in Country Exclusion table)",
                                        "custom_msg" => ""],
                                    $index.'_32' => ["wpay_code" => "63",
                                        "wpay_desc" => "Unable to authorise OR ERROR",
                                        "custom_msg" => ""],
                                    $index.'_33' => ["wpay_code" => "64",
                                        "wpay_desc" => "Unable to authorise",
                                        "custom_msg" => ""],
                                    $index.'_34' => ["wpay_code" => "65",
                                        "wpay_desc" => "Exceeds withdrawal count limit OR Authentication requested",
                                        "custom_msg" => ""],
                                    $index.'_35' => ["wpay_code" => "68",
                                        "wpay_desc" => "Time out",
                                        "custom_msg" => ""],
                                    $index.'_36' => ["wpay_code" => "70",
                                        "wpay_desc" => "Contact Card Issuer",
                                        "custom_msg" => ""],
                                    $index.'_37' => ["wpay_code" => "71",
                                        "wpay_desc" => "PIN Not Changed",
                                        "custom_msg" => ""],
                                    $index.'_38' => ["wpay_code" => "75",
                                        "wpay_desc" => "Allowable number of PIN tries exceeded",
                                        "custom_msg" => ""],
                                    $index.'_39' => ["wpay_code" => "76",
                                        "wpay_desc" => "Invalid/nonexistent OR Invalid/nonexistent specified",
                                        "custom_msg" => ""],
                                    $index.'_40' => ["wpay_code" => "77",
                                        "wpay_desc" => "Invalid/nonexistent OR Invalid/nonexistent specified",
                                        "custom_msg" => ""],
                                    $index.'_41' => ["wpay_code" => "78",
                                        "wpay_desc" => "Invalid/nonexistent account specified (general)",
                                        "custom_msg" => ""],
                                    $index.'_42' => ["wpay_code" => "79",
                                        "wpay_desc" => "Already reversed",
                                        "custom_msg" => ""],
                                    $index.'_43' => ["wpay_code" => "80",
                                        "wpay_desc" => "Visa transactions: credit issuer unavailable.",
                                        "custom_msg" => ""],
                                    $index.'_44' => ["wpay_code" => "82",
                                        "wpay_desc" => "Negative CAM, dCVV, iCVV, or CVV results",
                                        "custom_msg" => ""],
                                    $index.'_45' => ["wpay_code" => "84",
                                        "wpay_desc" => "Invalid Authorization Life Cycle",
                                        "custom_msg" => ""],
                                    $index.'_46' => ["wpay_code" => "85",
                                        "wpay_desc" => "Not declined. Valid for AVS only, balance Inq OR No"
                                        . " reason to decline a request",
                                        "custom_msg" => ""],
                                    $index.'_47' => ["wpay_code" => "86",
                                        "wpay_desc" => "Cannot Verify PIN",
                                        "custom_msg" => ""],
                                    $index.'_48' => ["wpay_code" => "88",
                                        "wpay_desc" => "Unable to authorise",
                                        "custom_msg" => ""],
                                    $index.'_49' => ["wpay_code" => "89",
                                        "wpay_desc" => "Unacceptable PIN - Transaction Declined - Retry "
                                        . "OR Ineligible to receive",
                                        "custom_msg" => ""],
                                    $index.'_50' => ["wpay_code" => "91",
                                        "wpay_desc" => "Authorization System or issuer system inoperative"
                                        . " OR Authorization System or issuer system inop",
                                        "custom_msg" => ""],
                                    $index.'_51' => ["wpay_code" => "92",
                                        "wpay_desc" => "Unable to route transaction OR Destination cannot "
                                        . "be found for routing",
                                        "custom_msg" => ""],
                                    $index.'_52' => ["wpay_code" => "93",
                                        "wpay_desc" => "Transaction cannot be completed violation of law",
                                        "custom_msg" => ""],
                                    $index.'_53' => ["wpay_code" => "94",
                                        "wpay_desc" => "Duplicate transmission detected",
                                        "custom_msg" => ""],
                                    $index.'_54' => ["wpay_code" => "96",
                                        "wpay_desc" => "System error OR Unable to authorise",
                                        "custom_msg" => ""],
                                    $index.'_55' => ["wpay_code" => "98",
                                        "wpay_desc" => "ERROR",
                                        "custom_msg" => ""],
                                    $index.'_56' => ["wpay_code" => "99",
                                        "wpay_desc" => "ERROR",
                                        "custom_msg" => ""],
                                    $index.'_57' => ["wpay_code" => "397",
                                        "wpay_desc" => "Surcharge amount not permitted on Visa",
                                        "custom_msg" => ""],
                                    $index.'_58' => ["wpay_code" => "398",
                                        "wpay_desc" => "Surcharge not supported",
                                        "custom_msg" => ""],
                                    $index.'_59' => ["wpay_code" => "442",
                                        "wpay_desc" => "Acquirer Institution Identification Code in the request "
                                        . "message is not registered at CAFIS",
                                        "custom_msg" => ""],
                                    $index.'_60' => ["wpay_code" => "443",
                                        "wpay_desc" => "CAFIS System Error. Try again",
                                        "custom_msg" => ""],
                                    $index.'_61' => ["wpay_code" => "444",
                                        "wpay_desc" => "The acquirer system is busy. Try again",
                                        "custom_msg" => ""],
                                    $index.'_62' => ["wpay_code" => "445",
                                        "wpay_desc" => "Acquirer system error. Try again",
                                        "custom_msg" => ""],
                                    $index.'_63' => ["wpay_code" => "446",
                                        "wpay_desc" => "The acquirer system has closed. Try again",
                                        "custom_msg" => ""],
                                    $index.'_64' => ["wpay_code" => "447",
                                        "wpay_desc" => "CAFIS System Error. Try again",
                                        "custom_msg" => ""],
                                    $index.'_65' => ["wpay_code" => "448",
                                        "wpay_desc" => "Illegal encoding format. Try again",
                                        "custom_msg" => ""],
                                    $index.'_66' => ["wpay_code" => "449",
                                        "wpay_desc" => "CAFIS System Error. Try again",
                                        "custom_msg" => ""],
                                    $index.'_67' => ["wpay_code" => "450",
                                        "wpay_desc" => "CAFIS System Error. Try again",
                                        "custom_msg" => ""],
                                    $index.'_68' => ["wpay_code" => "451",
                                        "wpay_desc" => "Advice message already received",
                                        "custom_msg" => ""],
                                    $index.'_69' => ["wpay_code" => "452",
                                        "wpay_desc" => "CAFIS detected a timeout when it sent the message "
                                        . "to the acquirer. Try again",
                                        "custom_msg" => ""],
                                    $index.'_70' => ["wpay_code" => "453",
                                        "wpay_desc" => "CAFIS System Error. Try again",
                                        "custom_msg" => ""],
                                    $index.'_71' => ["wpay_code" => "454",
                                        "wpay_desc" => "CAFIS System Error. Try again",
                                        "custom_msg" => ""],
                                    $index.'_72' => ["wpay_code" => "455",
                                        "wpay_desc" => "CAFIS System Error. Try again",
                                        "custom_msg" => ""],
                                    $index.'_73' => ["wpay_code" => "456",
                                        "wpay_desc" => "CAFIS System Error. Try again",
                                        "custom_msg" => ""],
                                    $index.'_74' => ["wpay_code" => "457",
                                        "wpay_desc" => "CAFIS System Error. Try again",
                                        "custom_msg" => ""],
                                    $index.'_75' => ["wpay_code" => "458",
                                        "wpay_desc" => "The acquirer does not support the service",
                                        "custom_msg" => ""],
                                    $index.'_76' => ["wpay_code" => "459",
                                        "wpay_desc" => "CAFIS System Error. Try again",
                                        "custom_msg" => ""],
                                    $index.'_77' => ["wpay_code" => "460",
                                        "wpay_desc" => "CAFIS System Error. Try again",
                                        "custom_msg" => ""],
                                    $index.'_78' => ["wpay_code" => "577",
                                        "wpay_desc" => "The card is unusable.",
                                        "custom_msg" => ""],
                                    $index.'_79' => ["wpay_code" => "578",
                                        "wpay_desc" => "The transaction is pending.",
                                        "custom_msg" => ""],
                                    $index.'_80' => ["wpay_code" => "579",
                                        "wpay_desc" => "PIN is incorrect",
                                        "custom_msg" => ""],
                                    $index.'_81' => ["wpay_code" => "580",
                                        "wpay_desc" => "Security Code is incorrect",
                                        "custom_msg" => ""],
                                    $index.'_82' => ["wpay_code" => "581",
                                        "wpay_desc" => "Security Code is not set",
                                        "custom_msg" => ""],
                                    $index.'_83' => ["wpay_code" => "582",
                                        "wpay_desc" => "JIS2 stripe information is invalid",
                                        "custom_msg" => ""],
                                    $index.'_84' => ["wpay_code" => "583",
                                        "wpay_desc" => "The card is maxed out for the day. (insufficient funds)",
                                        "custom_msg" => ""],
                                    $index.'_85' => ["wpay_code" => "584",
                                        "wpay_desc" => "The amount exceeds the limit for the day. "
                                        . "(insufficient funds)",
                                        "custom_msg" => ""],
                                    $index.'_86' => ["wpay_code" => "586",
                                        "wpay_desc" => "The card is invalid. (MOD 10 check failed)",
                                        "custom_msg" => ""],
                                    $index.'_87' => ["wpay_code" => "587",
                                        "wpay_desc" => "The card is invalid (lost/stolen).",
                                        "custom_msg" => ""],
                                    $index.'_88' => ["wpay_code" => "588",
                                        "wpay_desc" => "The card is invalid. (MOD 10 check failed)",
                                        "custom_msg" => ""],
                                    $index.'_89' => ["wpay_code" => "589",
                                        "wpay_desc" => "Message element Primary Account Number value is invalid "
                                        . "(MOD 10 check failed)",
                                        "custom_msg" => ""],
                                    $index.'_90' => ["wpay_code" => "590",
                                        "wpay_desc" => "Message element Merchant Type value is invalid",
                                        "custom_msg" => ""],
                                    $index.'_91' => ["wpay_code" => "591",
                                        "wpay_desc" => "Message element Transaction Amount value is invalid",
                                        "custom_msg" => ""],
                                    $index.'_91' => ["wpay_code" => "592",
                                        "wpay_desc" => "Message element Tax and Postage value is invalid",
                                        "custom_msg" => ""],
                                    $index.'_92' => ["wpay_code" => "593",
                                        "wpay_desc" => "Bonus Count value is invalid",
                                        "custom_msg" => ""],
                                    $index.'_93' => ["wpay_code" => "594",
                                        "wpay_desc" => "Bonus Count value is invalid",
                                        "custom_msg" => ""],
                                    $index.'_94' => ["wpay_code" => "595",
                                        "wpay_desc" => "Bonus Count value is invalid",
                                        "custom_msg" => ""],
                                    $index.'_95' => ["wpay_code" => "596",
                                        "wpay_desc" => "First Payment Month value is invalid",
                                        "custom_msg" => ""],
                                    $index.'_96' => ["wpay_code" => "597",
                                        "wpay_desc" => "Instalment Count value is invalid",
                                        "custom_msg" => ""],
                                    $index.'_97' => ["wpay_code" => "598",
                                        "wpay_desc" => "Instalment Amount value is invalid",
                                        "custom_msg" => ""],
                                    $index.'_98' => ["wpay_code" => "599",
                                        "wpay_desc" => "First Payment Amount value is invalid",
                                        "custom_msg" => ""],
                                    $index.'_99' => ["wpay_code" => "600",
                                        "wpay_desc" => "Message elements Service Code, "
                                        . "Business Code and Message Code value is invalid",
                                        "custom_msg" => ""],
                                    $index.'_100' => ["wpay_code" => "601",
                                        "wpay_desc" => "Message element Payment Division value is invalid",
                                        "custom_msg" => ""],
                                    $index.'_101' => ["wpay_code" => "602",
                                        "wpay_desc" => "Message element Inquiry Division value is invalid",
                                        "custom_msg" => ""],
                                    $index.'_102' => ["wpay_code" => "603",
                                        "wpay_desc" => "Message element Cancel Division value is invalid",
                                        "custom_msg" => ""],
                                    $index.'_103' => ["wpay_code" => "604",
                                        "wpay_desc" => "Message element Original Payment Division value is invalid",
                                        "custom_msg" => ""],
                                    $index.'_104' => ["wpay_code" => "605",
                                        "wpay_desc" => "The card is expired.",
                                        "custom_msg" => ""],
                                    $index.'_105' => ["wpay_code" => "606",
                                        "wpay_desc" => "The card is not applicable to the service.",
                                        "custom_msg" => ""],
                                    $index.'_106' => ["wpay_code" => "607",
                                        "wpay_desc" => "The acquirer service is completed.",
                                        "custom_msg" => ""],
                                    $index.'_107' => ["wpay_code" => "608",
                                        "wpay_desc" => "The invalid card (lost/stolen) has an error.",
                                        "custom_msg" => ""],
                                    $index.'_108' => ["wpay_code" => "609",
                                        "wpay_desc" => "The request message cannot be processed for some reason.",
                                        "custom_msg" => ""],
                                    $index.'_109' => ["wpay_code" => "610",
                                        "wpay_desc" => "The request message for which a transaction "
                                        . "is not supported is received",
                                        "custom_msg" => ""],
                                    $index.'_110' => ["wpay_code" => "611",
                                        "wpay_desc" => "The request message from a centre which has not "
                                        . "made the contact.",
                                        "custom_msg" => ""],
                                    $index.'_111' => ["wpay_code" => "622",
                                        "wpay_desc" => "Message element Service Code setting error",
                                        "custom_msg" => ""],
                                    $index.'_112' => ["wpay_code" => "623",
                                        "wpay_desc" => "Message element Business Code setting error",
                                        "custom_msg" => ""],
                                    $index.'_113' => ["wpay_code" => "624",
                                        "wpay_desc" => "Message element Message Code setting error",
                                        "custom_msg" => ""],
                                    $index.'_114' => ["wpay_code" => "625",
                                        "wpay_desc" => "Message element Processor Code setting error",
                                        "custom_msg" => ""],
                                    $index.'_115' => ["wpay_code" => "626",
                                        "wpay_desc" => "Message element Merchant Code setting error",
                                        "custom_msg" => ""],
                                    $index.'_116' => ["wpay_code" => "627",
                                        "wpay_desc" => "Message element Transaction Identifier setting error",
                                        "custom_msg" => ""],
                                    $index.'_117' => ["wpay_code" => "628",
                                        "wpay_desc" => "Message element Processor Transaction Date-Time setting error",
                                        "custom_msg" => ""],
                                    $index.'_118' => ["wpay_code" => "629",
                                        "wpay_desc" => "Message element Card Acceptor Terminal Identification "
                                        . "setting error",
                                        "custom_msg" => ""],
                                    $index.'_119' => ["wpay_code" => "630",
                                        "wpay_desc" => "Message element Encryption Method Code setting error",
                                        "custom_msg" => ""],
                                    $index.'_120' => ["wpay_code" => "631",
                                        "wpay_desc" => "Message element Key Encryption Key Index in Use setting error",
                                        "custom_msg" => ""],
                                    $index.'_121' => ["wpay_code" => "632",
                                        "wpay_desc" => "Message element Processor Authentication Key Index "
                                        . "setting error",
                                        "custom_msg" => ""],
                                    $index.'_122' => ["wpay_code" => "633",
                                        "wpay_desc" => "Message element Message Encryption Key setting error",
                                        "custom_msg" => ""],
                                    $index.'_123' => ["wpay_code" => "634",
                                        "wpay_desc" => "Message element Message Authentication Code setting error",
                                        "custom_msg" => ""],
                                    $index.'_124' => ["wpay_code" => "635",
                                        "wpay_desc" => "Message element Acquirer Institution Identification "
                                        . "Code setting error",
                                        "custom_msg" => ""],
                                    $index.'_125' => ["wpay_code" => "636",
                                        "wpay_desc" => "Message element Primary Account Number setting error",
                                        "custom_msg" => ""],
                                    $index.'_126' => ["wpay_code" => "637",
                                        "wpay_desc" => "Message element Expiration Date setting error",
                                        "custom_msg" => ""],
                                    $index.'_127' => ["wpay_code" => "638",
                                        "wpay_desc" => "Message element Track-2 Data setting error",
                                        "custom_msg" => ""],
                                    $index.'_128' => ["wpay_code" => "639",
                                        "wpay_desc" => "Message element PIN Data setting error",
                                        "custom_msg" => ""],
                                    $index.'_129' => ["wpay_code" => "640",
                                        "wpay_desc" => "Message element Merchant Type setting error",
                                        "custom_msg" => ""],
                                    $index.'_130' => ["wpay_code" => "641",
                                        "wpay_desc" => "Message element Transaction Amount setting error",
                                        "custom_msg" => ""],
                                    $index.'_131' => ["wpay_code" => "642",
                                        "wpay_desc" => "Message element Tax and Postage setting error",
                                        "custom_msg" => ""],
                                    $index.'_132' => ["wpay_code" => "643",
                                        "wpay_desc" => "Message element Point of Service Data Code setting error",
                                        "custom_msg" => ""],
                                    $index.'_133' => ["wpay_code" => "644",
                                        "wpay_desc" => "Message element Payment Division setting error",
                                        "custom_msg" => ""],
                                    $index.'_134' => ["wpay_code" => "645",
                                        "wpay_desc" => "Message element Cancel Division setting error",
                                        "custom_msg" => ""],
                                    $index.'_135' => ["wpay_code" => "646",
                                        "wpay_desc" => "Message element Original Terminal Processing Serial Number"
                                        . " setting error",
                                        "custom_msg" => ""],
                                    $index.'_136' => ["wpay_code" => "647",
                                        "wpay_desc" => "Message element Original Payment Division setting error",
                                        "custom_msg" => ""],
                                    $index.'_137' => ["wpay_code" => "659",
                                        "wpay_desc" => "Message element Original Processor Transaction Date-Time "
                                        . "setting error",
                                        "custom_msg" => ""],
                                    $index.'_138' => ["wpay_code" => "660",
                                        "wpay_desc" => "Message element Free Field setting error",
                                        "custom_msg" => ""],
                                    $index.'_139' => ["wpay_code" => "661",
                                        "wpay_desc" => "Message element Terminal Processing Serial Number "
                                        . "setting error",
                                        "custom_msg" => ""],
                                    $index.'_140' => ["wpay_code" => "662",
                                        "wpay_desc" => "Message element Security Code setting error",
                                        "custom_msg" => ""],
                                    $index.'_141' => ["wpay_code" => "663",
                                        "wpay_desc" => "Message element Electronic Commerce Indicator setting error",
                                        "custom_msg" => ""],
                                    $index.'_142' => ["wpay_code" => "664",
                                        "wpay_desc" => "Message element XID setting error",
                                        "custom_msg" => ""],
                                    $index.'_143' => ["wpay_code" => "665",
                                        "wpay_desc" => "Message element Accountholder Authentication "
                                        . "Value setting error",
                                        "custom_msg" => ""],
                                    $index.'_144' => ["wpay_code" => "666",
                                        "wpay_desc" => "Message element Transaction Status setting error",
                                        "custom_msg" => ""],
                                    $index.'_145' => ["wpay_code" => "667",
                                        "wpay_desc" => "Message element Message Version Number setting error",
                                        "custom_msg" => ""],
                                    $index.'_146' => ["wpay_code" => "668",
                                        "wpay_desc" => "Message element CAVV Algorithm setting error",
                                        "custom_msg" => ""],
                                    $index.'_147' => ["wpay_code" => "669",
                                        "wpay_desc" => "Message element Recurring Flag setting error",
                                        "custom_msg" => ""],
                                    $index.'_148' => ["wpay_code" => "670",
                                        "wpay_desc" => "Element Block (e.g. General Transaction Info setting error)",
                                        "custom_msg" => ""],
                                    $index.'_149' => ["wpay_code" => "671",
                                        "wpay_desc" => "Combination of message elements Service Code and Message"
                                        . " Code setting is incorrect",
                                        "custom_msg" => ""],
                                    $index.'_150' => ["wpay_code" => "672",
                                        "wpay_desc" => "Message element Processor Authentication Key Index "
                                        . "setting error",
                                        "custom_msg" => ""],
                                    $index.'_151' => ["wpay_code" => "673",
                                        "wpay_desc" => "There is more than one original transaction to reverse. "
                                        . "Unable to reverse the transaction",
                                        "custom_msg" => ""],
                                    $index.'_152' => ["wpay_code" => "674",
                                        "wpay_desc" => "Combination of message elements Processor Code and Merchant "
                                        . "Code setting is incorrect",
                                        "custom_msg" => ""],
                                    $index.'_153' => ["wpay_code" => "675",
                                        "wpay_desc" => "Global GEAR System Error. Try Again.",
                                        "custom_msg" => ""],
                                    $index.'_154' => ["wpay_code" => "676",
                                        "wpay_desc" => "Global GEAR System Error. Transaction result is unclear.",
                                        "custom_msg" => ""],
                                    $index.'_155' => ["wpay_code" => "677",
                                        "wpay_desc" => "Global GEAR System Error. Transaction result is unclear.",
                                        "custom_msg" => ""],
                                    $index.'_156' => ["wpay_code" => "678",
                                        "wpay_desc" => "Global GEAR System Error. Try Again.",
                                        "custom_msg" => ""],
                                    $index.'_157' => ["wpay_code" => "679",
                                        "wpay_desc" => "Global GEAR System Error. Transaction result is unclear.",
                                        "custom_msg" => ""],
                                    $index.'_158' => ["wpay_code" => "680",
                                        "wpay_desc" => "Global GEAR System Error. Transaction result is unclear.",
                                        "custom_msg" => ""],
                                    $index.'_159' => ["wpay_code" => "681",
                                        "wpay_desc" => "Global GEAR System Error. Try Again.",
                                        "custom_msg" => ""],
                                    $index.'_160' => ["wpay_code" => "682",
                                        "wpay_desc" => "Global GEAR System Error. Transaction result is unclear.",
                                        "custom_msg" => ""],
                                    $index.'_161' => ["wpay_code" => "683",
                                        "wpay_desc" => "Global GEAR System Error. Transaction result is unclear.",
                                        "custom_msg" => ""],
                                    $index.'_162' => ["wpay_code" => "694",
                                        "wpay_desc" => "Global GEAR System Error. Transaction result is unclear.",
                                        "custom_msg" => ""],
                                    $index.'_163' => ["wpay_code" => "695",
                                        "wpay_desc" => "Global GEAR System Error. Transaction result is unclear.",
                                        "custom_msg" => ""],
                                    $index.'_164' => ["wpay_code" => "696",
                                        "wpay_desc" => "Global GEAR System Error. Transaction result is unclear.",
                                        "custom_msg" => ""],
                                    $index.'_165' => ["wpay_code" => "697",
                                        "wpay_desc" => "Global GEAR System Error. Transaction result is unclear.",
                                        "custom_msg" => ""],
                                    $index.'_166' => ["wpay_code" => "698",
                                        "wpay_desc" => "Global GEAR System Error. Transaction result is unclear.",
                                        "custom_msg" => ""],
                                    $index.'_167' => ["wpay_code" => "699",
                                        "wpay_desc" => "Global GEAR System Error. Transaction result is unclear.",
                                        "custom_msg" => ""],
                                    $index.'_168' => ["wpay_code" => "700",
                                        "wpay_desc" => "Global GEAR System Error. Transaction result is unclear.",
                                        "custom_msg" => ""],
                                    $index.'_169' => ["wpay_code" => "701",
                                        "wpay_desc" => "Global GEAR System Error. Transaction result is unclear.",
                                        "custom_msg" => ""],
                                    $index.'_170' => ["wpay_code" => "702",
                                        "wpay_desc" => "Global GEAR System Error. Transaction result is unclear.",
                                        "custom_msg" => ""],
                                    $index.'_171' => ["wpay_code" => "703",
                                        "wpay_desc" => "Global GEAR System Error. Try Again.",
                                        "custom_msg" => ""],
                                    $index.'_172' => ["wpay_code" => "704",
                                        "wpay_desc" => "Global GEAR System Error. Transaction result is unclear.",
                                        "custom_msg" => ""],
                                    $index.'_173' => ["wpay_code" => "705",
                                        "wpay_desc" => "Global GEAR System Error. Try Again.",
                                        "custom_msg" => ""],
                                    $index.'_174' => ["wpay_code" => "828",
                                        "wpay_desc" => "Unable to authorise",
                                        "custom_msg" => ""],
                                    $index.'_175' => ["wpay_code" => "831",
                                        "wpay_desc" => "Cash service not available",
                                        "custom_msg" => ""],
                                    $index.'_176' => ["wpay_code" => "832",
                                        "wpay_desc" => "Cash back request exceeds issuer limit",
                                        "custom_msg" => ""],
                                    $index.'_177' => ["wpay_code" => "833",
                                        "wpay_desc" => "Resubmitted transaction over max days limit",
                                        "custom_msg" => ""],
                                    $index.'_178' => ["wpay_code" => "835",
                                        "wpay_desc" => "Decline for CVV2 failure",
                                        "custom_msg" => ""],
                                    $index.'_179' => ["wpay_code" => "836",
                                        "wpay_desc" => "Transaction amount greater than preauthorised",
                                        "custom_msg" => ""],
                                    $index.'_180' => ["wpay_code" => "902",
                                        "wpay_desc" => "Invalid biller information",
                                        "custom_msg" => ""],
                                    $index.'_181' => ["wpay_code" => "905",
                                        "wpay_desc" => "Unable to authorise",
                                        "custom_msg" => ""],
                                    $index.'_182' => ["wpay_code" => "906",
                                        "wpay_desc" => "Unable to authorise",
                                        "custom_msg" => ""],
                                    $index.'_183' => ["wpay_code" => "937",
                                        "wpay_desc" => "Card Authentication failed",
                                        "custom_msg" => ""],
                                    $index.'_184' => ["wpay_code" => "972",
                                        "wpay_desc" => "Stop Payment Order",
                                        "custom_msg" => ""],
                                    $index.'_185' => ["wpay_code" => "973",
                                        "wpay_desc" => "Revocation of Authorization Order",
                                        "custom_msg" => ""],
                                    $index.'_186' => ["wpay_code" => "975",
                                        "wpay_desc" => "Revocation of All Authorizations Order",
                                        "custom_msg" => ""],
                                    $index.'_187' => ["wpay_code" => "1044",
                                        "wpay_desc" => "Approval, keep first check",
                                        "custom_msg" => ""],
                                    $index.'_188' => ["wpay_code" => "1045",
                                        "wpay_desc" => "Check OK, no conversion",
                                        "custom_msg" => ""],
                                    $index.'_189' => ["wpay_code" => "1046",
                                        "wpay_desc" => "Invalid RTTN",
                                        "custom_msg" => ""],
                                    $index.'_190' => ["wpay_code" => "1047",
                                        "wpay_desc" => "Amount greater than limit",
                                        "custom_msg" => ""],
                                    $index.'_191' => ["wpay_code" => "1048",
                                        "wpay_desc" => "Unpaid items, failed NEG",
                                        "custom_msg" => ""],
                                    $index.'_192' => ["wpay_code" => "1049",
                                        "wpay_desc" => "Duplicate check number",
                                        "custom_msg" => ""],
                                    $index.'_193' => ["wpay_code" => "1050",
                                        "wpay_desc" => "MICR error",
                                        "custom_msg" => ""],
                                    $index.'_194' => ["wpay_code" => "1051",
                                        "wpay_desc" => "Too many checks",
                                        "custom_msg" => ""],
                                    $index.'_195' => ["wpay_code" => "1198",
                                        "wpay_desc" => "Forward to issuer",
                                        "custom_msg" => ""],
                                    $index.'_196' => ["wpay_code" => "1201",
                                        "wpay_desc" => "Forward to issuer",
                                        "custom_msg" => ""],
                                    $index.'_197' => ["wpay_code" => "1263",
                                        "wpay_desc" => "Unable to authorise",
                                        "custom_msg" => ""],
                                    $index.'_198' => ["wpay_code" => "1295",
                                        "wpay_desc" => "Unknown",
                                        "custom_msg" => ""],
                                ];
             $resultArray = [];
        foreach ($responseValue as $row) {
            $payment_type = $row['wpay_code'];
            $rs['wpay_desc'] = $row['wpay_desc'];
            $rs['custom_msg'] = $row['custom_msg'];
            $resultArray[$payment_type] = $rs;
        }
             $responseCodes = $this->serializer->serialize($resultArray);
             $configData = [
                'section' => 'worldpay_exceptions',
                'website' => null,
                'store'   => null,
                'groups'  => [
                    'extended_response_codes' => [
                        'fields' => [
                            'response_codes' => [
                                'value' => $responseCodes

                            ],
                        ],
                    ],
                ],
             ];
        /** @var \Magento\Config\Model\Config $configModel */
             $configModel = $this->configFactory->create(['data' => $configData]);
             $configModel->save();
    }
        /**
         *  Save CC labels
         */
    public function saveCCLabels()
    {

        $index = time();
        $exceptionValues = [$index . '_0' => ["exception_code" => "CCAM0",
                "exception_messages" => "The card number entered is invalid.",
                "exception_module_messages" => ""],
            $index . '_1' => ["exception_code" => "CCAM1",
                "exception_messages" => "Card number should contain between 12 and "
                . "20 numeric characters.",
                "exception_module_messages" => ""],
            $index . '_2' => ["exception_code" => "CCAM2",
                "exception_messages" => "This card number cannot be used to place "
                . "3DS2 order now, please go and update this from My account first.",
                "exception_module_messages" => ""],
            $index . '_3' => ["exception_code" => "CCAM3",
                "exception_messages" => "Please, enter valid Card Verification Number",
                "exception_module_messages" => ""],
            $index . '_4' => ["exception_code" => "CCAM4",
                "exception_messages" => "Please, Save the card before placing "
                . "subscription order",
                "exception_module_messages" => ""],
            $index . '_5' => ["exception_code" => "CCAM5",
                "exception_messages" => "Please, Verify the disclaimer! before saving"
                . " the card",
                "exception_module_messages" => ""],
            $index . '_6' => ["exception_code" => "CCAM6",
                "exception_messages" => "Please select one of the options",
                "exception_module_messages" => ""],
            $index . '_7' => ["exception_code" => "CCAM7",
                "exception_messages" => "Failed due to unrecoverable error",
                "exception_module_messages" => ""],
            $index . '_8' => ["exception_code" => "CCAM8",
                "exception_messages" => "3DS Failed",
                "exception_module_messages" => ""],
            $index . '_9' => ["exception_code" => "CCAM9",
                "exception_messages" => "Unfortunately the order could not be processed. "
                . "Please contact us or try again later.",
                "exception_module_messages" => ""],
            $index . '_10' => ["exception_code" => "CCAM10",
                "exception_messages" => "An error occurred",
                "exception_module_messages" => ""],
            $index . '_11' => ["exception_code" => "CCAM11",
                "exception_messages" => "An error occurred on the server. Please try to "
                . "place the order again.",
                "exception_module_messages" => ""],
            $index . '_12' => ["exception_code" => "CCAM12",
                "exception_messages" => "3DS2 services are disabled, please contact "
                . "system administrator.",
                "exception_module_messages" => ""],
            $index . '_13' => ["exception_code" => "CCAM13",
                "exception_messages" => "Invalid Payment Type. Please Refresh and "
                . "check again",
                "exception_module_messages" => ""],
            $index . '_14' => ["exception_code" => "CCAM14",
                "exception_messages" => "WorldPay refund ERROR: Credit Memo does not "
                . "match Order. Reference",
                "exception_module_messages" => ""],
            $index . '_15' => ["exception_code" => "CCAM15",
                "exception_messages" => "Unfortunately the order could not be processed."
                . " Please contact us or try again later",
                "exception_module_messages" => ""],
            $index . '_16' => ["exception_code" => "CCAM16",
                "exception_messages" => "Duplicate Entry, This card number is already "
                . "saved.",
                "exception_module_messages" => ""],
            $index . '_17' => ["exception_code" => "CCAM17",
                "exception_messages" => "Order %s has been declined, please check your "
                . "details and try again",
                "exception_module_messages" => ""],
            $index . '_18' => ["exception_code" => "CCAM18",
                "exception_messages" => "An unexpected error occurred, Please try to "
                . "place the order again",
                "exception_module_messages" => ""],
            $index . '_19' => ["exception_code" => "CCAM19",
                "exception_messages" => "There appears to be an issue with your stored "
                . "data, please review in your account and update details as applicable.",
                "exception_module_messages" => ""],
            $index . '_20' => ["exception_code" => "CCAM20",
                "exception_messages" => "CPF is 11 digits and CNPJ is 14 digits.",
                "exception_module_messages" => ""],
            $index . '_21' => ["exception_code" => "CCAM21",
                "exception_messages" => "Only alphabet,number or space is allowed",
                "exception_module_messages" => ""],
            $index . '_22' => ["exception_code" => "CCAM22",
                "exception_messages" => "You already seem to have this card number "
                . "stored, If your card details have changed, you can update them via"
                . " My Account -> Saved Card", "exception_module_messages" => ""],
            $index . '_23' => ["exception_code" => "CCAM23",
                "exception_messages" => "Parse error with PaRes: Error parsing pARes",
                "exception_module_messages" => ""],
            $index . '_24' => ["exception_code" => "CCAM24",
                "exception_messages" => "Invalid Configuration. Please Refresh and check again",
                "exception_module_messages" => ""],
            $index . '_25' => ["exception_code" => "CCAM25",
                "exception_messages" => "Invalid Expiry Year. Please Refresh and check again",
                "exception_module_messages" => ""],
            $index . '_26' => ["exception_code" => "CCAM26",
                "exception_messages" => "Invalid Expiry Month. Please Refresh and check again",
                "exception_module_messages" => ""],
            $index . '_27' => ["exception_code" => "CCAM27",
                "exception_messages" => "Invalid Card Number. Please Refresh and check again",
                "exception_module_messages" => ""],
            $index . '_28' => ["exception_code" => "CCAM28",
                "exception_messages" => "Invalid Card Holder Name.Please Refresh and check again",
                "exception_module_messages" => ""],
            $index . '_29' => ["exception_code" => "CCAM29",
                "exception_messages" => "Order has already been paid",
                "exception_module_messages" => ""],
        ];

        $exceptionCodes = $this->convertArrayToString($exceptionValues);
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
        /** @var \Magento\Config\Model\Config $configModel */
        $configModel = $this->configFactory->create(['data' => $configData]);
        $configModel->save();
    }
    /**
     * Save My account alerts codes
     */
    public function saveMyAccountAlertsCodes()
    {
        $index = time();
        $exceptionValues = [ $index.'_0' => ["exception_code" => "MCAM0",
                "exception_messages" => "You should login or register "
                . "to buy a subscription.",
                "exception_module_messages" => ""],
            $index . '_1' => ["exception_code" => "MCAM1",
                "exception_messages" => "Choose any of the plan!",
                "exception_module_messages" => ""],
            $index . '_2' => ["exception_code" => "MCAM2",
                "exception_messages" => "Choose plan start date!",
                "exception_module_messages" => ""],
            $index . '_3' => ["exception_code" => "MCAM3",
                "exception_messages" => "Are you sure you would like to remove this item "
                . "from the shopping cart?", "exception_module_messages" => ""],
            $index . '_4' => ["exception_code" => "MCAM4",
                "exception_messages" => "An error has occurred. Please try again.",
                "exception_module_messages" => ""],
            $index . '_5' => ["exception_code" => "MCAM5",
                "exception_messages" => "Item is deleted successfully",
                "exception_module_messages" => ""],
            $index . '_6' => ["exception_code" => "MCAM6",
                "exception_messages" => "Please try after some time",
                "exception_module_messages" => ""],
            $index . '_7' => ["exception_code" => "MCAM7",
                "exception_messages" => "Error: the card has not been updated.",
                "exception_module_messages" => ""],
            $index . '_8' => ["exception_code" => "MCAM8",
                "exception_messages" => "Error occurred, please check your card details.",
                "exception_module_messages" => ""],
            $index . '_9' => ["exception_code" => "MCAM9",
                "exception_messages" => "The card has been updated.",
                "exception_module_messages" => ""],
            $index . '_10' => ["exception_code" => "MCAM10",
                "exception_messages" => "Subscription has been updated.",
                "exception_module_messages" => ""],
            $index . '_11' => ["exception_code" => "MCAM11",
                "exception_messages" => "Failed to update subscription.",
                "exception_module_messages" => ""],
            $index . '_12' => ["exception_code" => "MCAM12",
                "exception_messages" => "Are you sure you want to cancel "
                . "this subscription?",
                "exception_module_messages" => ""],
            $index . '_13' => ["exception_code" => "MCAM13",
                "exception_messages" => "You have not purchased any subscriptions yet.",
                "exception_module_messages" => ""],
            $index . '_14' => ["exception_code" => "MCAM14",
                "exception_messages" => "Subscription has been cancelled.",
                "exception_module_messages" => ""],
            $index . '_15' => ["exception_code" => "MCAM15",
                "exception_messages" => "Subscription no longer exists.",
                "exception_module_messages" => ""],
            $index . '_16' => ["exception_code" => "MCAM16",
                "exception_messages" => "Subscription is no longer active.",
                "exception_module_messages" => ""],
            $index . '_17' => ["exception_code" => "MCAM17",
                "exception_messages" => "Subscription is not found",
                "exception_module_messages" => ""],
            $index . '_18' => ["exception_code" => "MCAM18",
                "exception_messages" => "Failed to cancel subscription.",
                "exception_module_messages" => ""],
            $index . '_19' => ["exception_code" => "MCAM19",
                "exception_messages" => "Subscriptions can be bought only separately,"
                . "one subscription at a time.",
                "exception_module_messages" => ""],
            $index . '_20' => ["exception_code" => "MCAM20",
                "exception_messages" => "Selected subscription plan is not available.",
                "exception_module_messages" => ""],
            $index . '_21' => ["exception_code" => "MCAM21",
                "exception_messages" => "Subscription already in the cart",
                "exception_module_messages" => ""],
            $index . '_22' => ["exception_code" => "MCAM22",
                "exception_messages" => "Choose plan end date!",
                "exception_module_messages" => ""],
        ];
        $exceptionCodes = $this->convertArrayToString($exceptionValues);
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
        /**
         *  Save Admin Labels Exceptions
         */
    public function saveAdminLabelsExceptions()
    {
        $index = time();
        $exceptionValues = [ $index.'_0' => ["exception_code" => "ACAM0",
        "exception_messages" => "Something went wrong, please reload the page",
        "exception_module_messages" => ""],
        $index.'_1' => ["exception_code" => "ACAM1",
        "exception_messages" => "Plan code should not exceed 25 characters.",
        "exception_module_messages" => ""],
        $index.'_2' => ["exception_code" => "ACAM2",
        "exception_messages" => "Plan with such code already exists",
        "exception_module_messages" => ""],
        $index.'_3' => ["exception_code" => "ACAM3",
        "exception_messages" => "Payment synchronized successfully!!",
        "exception_module_messages" => ""],
        $index.'_4' => ["exception_code" => "ACAM4",
        "exception_messages" => "Synchronising Payment Status failed",
        "exception_module_messages" => ""],
        $index.'_5' => ["exception_code" => "ACAM5",
        "exception_messages" => "WorldPay refund ERROR: Credit Memo "
        . "does not match Order. Reference", "exception_module_messages" => ""],
        $index.'_6' => ["exception_code" => "ACAM6",
        "exception_messages" => "Subscription is no longer active.",
        "exception_module_messages" => ""],
        $index.'_7' => ["exception_code" => "ACAM7",
        "exception_messages" => "Subscription is not found.",
        "exception_module_messages" => ""],
        $index.'_8' => ["exception_code" => "ACAM8",
        "exception_messages" => "Failed to cancel subscription.",
        "exception_module_messages" => ""],
        $index.'_9' => ["exception_code" => "ACAM9",
        "exception_messages" => "Subscription not found.",
        "exception_module_messages" => ""],
        $index.'_10' => ["exception_code" => "ACAM10",
        "exception_messages" => "Unable to update subscription plan "
        . "with code %1: %2",
        "exception_module_messages" => ""],
        $index.'_11' => ["exception_code" => "ACAM11",
        "exception_messages" => "The value %s is a required field.",
        "exception_module_messages" => ""],
        $index.'_12' => ["exception_code" => "ACAM12",
        "exception_messages" => "Error Code %s already exist!",
        "exception_module_messages" => ""],
        $index.'_13' => ["exception_code" => "ACAM13",
        "exception_messages" => "Detected only whitespace character for code",
        "exception_module_messages" => ""],
        $index.'_14' => ["exception_code" => "ACAM14",
        "exception_messages" => "This is multishipping order. You cannot able to cancelled the order",
        "exception_module_messages" => ""],
        ];

        $exceptionCodes = $this->convertArrayToString($exceptionValues);
        $configData = [
            'section' => 'worldpay_exceptions',
            'website' => null,
            'store'   => null,
            'groups'  => [
                'adminexceptions' => [
                    'fields' => [
                        'general_exception' => [
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

        /**
         *  Save Miscellaneous
         */
    public function saveMiscellaneous()
    {
        $index = time();
        $currencyExponentValues = [$index . '_0' => ["currency_code" => "BEF",
                "currency" => "Belgian Franc",
                "exponent" => "0"],
            $index . '_1' => ["currency_code" => "XOF",
                "currency" => "CFA Franc BCEAO",
                "exponent" => "0"],
            $index . '_2' => ["currency_code" => "XAF",
                "currency" => "CFA Franc BEAC",
                "exponent" => "0"],
            $index . '_3' => ["currency_code" => "XPF",
                "currency" => "CFP Franc",
                "exponent" => "0"],
            $index . '_4' => ["currency_code" => "KMF",
                "currency" => "Comoro Franc",
                "exponent" => "0"],
            $index . '_5' => ["currency_code" => "GRD",
                "currency" => "Greek Drachma",
                "exponent" => "0"],
            $index . '_6' => ["currency_code" => "GNF",
                "currency" => "Guinea Franc",
                "exponent" => "0"],
            $index . '_7' => ["currency_code" => "HUF",
                "currency" => "Hungarian Forint",
                "exponent" => "0"],
            $index . '_8' => ["currency_code" => "IDR",
                "currency" => "Indonesian Rupiah",
                "exponent" => "0"],
            $index . '_9' => ["currency_code" => "ITL",
                "currency" => "Italian Lira",
                "exponent" => "0"],
            $index . '_10' => ["currency_code" => "JPY",
                "currency" => "Japanese Yen",
                "exponent" => "0"],
            $index . '_11' => ["currency_code" => "LUF",
                "currency" => "Luxembourg Franc",
                "exponent" => "0"],
            $index . '_12' => ["currency_code" => "MGA",
                "currency" => "Malagasy Ariary",
                "exponent" => "0"],
            $index . '_13' => ["currency_code" => "MGF",
                "currency" => "Malagasy Franc",
                "exponent" => "0"],
            $index . '_14' => ["currency_code" => "PYG",
                "currency" => "Paraguayan Guarani",
                "exponent" => "0"],
            $index . '_15' => ["currency_code" => "PTE",
                "currency" => "Portugese Escudo",
                "exponent" => "0"],
            $index . '_16' => ["currency_code" => "RWF",
                "currency" => "Rwanda Franc",
                "exponent" => "0"],
            $index . '_17' => ["currency_code" => "KRW",
                "currency" => "South-Korean Won",
                "exponent" => "0"],
            $index . '_18' => ["currency_code" => "ESP",
                "currency" => "Spanish Peseta",
                "exponent" => "0"],
            $index . '_19' => ["currency_code" => "TRL",
                "currency" => "Turkish Lira",
                "exponent" => "0"],
            $index . '_20' => ["currency_code" => "VND",
                "currency" => "Vietnamese New Dong",
                "exponent" => "0"],
            $index . '_21' => ["currency_code" => "BHD",
                "currency" => "Bahraini Dinar",
                "exponent" => "3"],
            $index . '_22' => ["currency_code" => "IQD",
                "currency" => "Iraqi Dinar",
                "exponent" => "3"],
            $index . '_23' => ["currency_code" => "JOD",
                "currency" => "Jordanian Dinar",
                "exponent" => "3"],
            $index . '_24' => ["currency_code" => "KWD",
                "currency" => "Kuwaiti Dinar",
                "exponent" => "3"],
            $index . '_25' => ["currency_code" => "LYD",
                "currency" => "Libyan Dinar",
                "exponent" => "3"],
            $index . '_26' => ["currency_code" => "OMR",
                "currency" => "Rial Omani",
                "exponent" => "3"],
            $index . '_27' => ["currency_code" => "TND",
                "currency" => "Tunisian Dinar",
                "exponent" => "3"],
        ];
        $resultArray = [];
        foreach ($currencyExponentValues as $row) {
            $payment_type = $row['currency_code'];
            $rs['currency'] = $row['currency'];
            $rs['exponent'] = $row['exponent'];
            $resultArray[$payment_type] = $rs;
        }
        $currencyExponentCodes = $this->serializer->serialize($resultArray);
        $configData = [
            'section' => 'worldpay',
            'website' => null,
            'store'   => null,
            'groups'  => [
                'miscellaneous' => [
                    'fields' => [
                        'currency_codes' => [
                            'value' => $currencyExponentCodes
                        ],
                    ],
                ],
            ],
        ];
        /** @var \Magento\Config\Model\Config $configModel */
        $configModel = $this->configFactory->create(['data' => $configData]);
        $configModel->save();
    }

            /**
             *  Save Checkout Labels
             */
    public function saveCheckoutLabels()
    {

        $index = time();
        $labelsValue = [ $index.'_0' => ["wpay_label_code" => "CO1",
                                    "wpay_label_desc" => "Credit Card Type",
                                    "wpay_custom_label" => ""],
                                $index.'_1' => ["wpay_label_code" => "CO2",
                                    "wpay_label_desc" => "We Accept",
                                    "wpay_custom_label" => ""],
                                $index.'_2' => ["wpay_label_code" => "CO3",
                                    "wpay_label_desc" => "Credit Card Number",
                                    "wpay_custom_label" => ""],
                                $index.'_3' => ["wpay_label_code" => "CO4",
                                    "wpay_label_desc" => "Card Holder Name",
                                    "wpay_custom_label" => ""],
                                $index.'_4' => ["wpay_label_code" => "CO5",
                                    "wpay_label_desc" => "CVV",
                                    "wpay_custom_label" => ""],
                                $index.'_5' => ["wpay_label_code" => "CO6",
                                    "wpay_label_desc" => "Month",
                                    "wpay_custom_label" => ""],
                                $index.'_6' => ["wpay_label_code" => "CO7",
                                    "wpay_label_desc" => "Year",
                                    "wpay_custom_label" => ""],
                                $index.'_7' => ["wpay_label_code" => "CO8",
                                    "wpay_label_desc" => "Save This Card",
                                    "wpay_custom_label" => ""],
                                $index.'_8' => ["wpay_label_code" => "CO9",
                                    "wpay_label_desc" => "Important Disclaimer!",
                                    "wpay_custom_label" => ""],
                                $index.'_9' => ["wpay_label_code" => "CO10",
                                    "wpay_label_desc" => "CPF/CNPJ",
                                    "wpay_custom_label" => ""],
                                $index.'_10' => ["wpay_label_code" => "CO11",
                                    "wpay_label_desc" => "Instalment",
                                    "wpay_custom_label" => ""],
                                $index.'_11' => ["wpay_label_code" => "CO12",
                                    "wpay_label_desc" => "Purpose of transaction",
                                    "wpay_custom_label" => ""],
                                $index.'_12' => ["wpay_label_code" => "CO13",
                                    "wpay_label_desc" => "Use Saved Card",
                                    "wpay_custom_label" => ""],
                                $index.'_13' => ["wpay_label_code" => "CO14",
                                    "wpay_label_desc" => "Place Order",
                                    "wpay_custom_label" => ""],
                                $index.'_14' => ["wpay_label_code" => "CO15",
                                    "wpay_label_desc" => "Bank Account Types",
                                    "wpay_custom_label" => ""],
                                $index.'_15' => ["wpay_label_code" => "CO16",
                                    "wpay_label_desc" => "Account Number",
                                    "wpay_custom_label" => ""],
                                $index.'_16' => ["wpay_label_code" => "CO17",
                                    "wpay_label_desc" => "Routing Number",
                                    "wpay_custom_label" => ""],
                                $index.'_17' => ["wpay_label_code" => "CO18",
                                    "wpay_label_desc" => "Check Number",
                                    "wpay_custom_label" => ""],
                                $index.'_18' => ["wpay_label_code" => "CO19",
                                    "wpay_label_desc" => "Company Name",
                                    "wpay_custom_label" => ""],
                                $index.'_19' => ["wpay_label_code" => "CO20",
                                    "wpay_label_desc" => "Email Address",
                                    "wpay_custom_label" => ""],
                                $index.'_20' => ["wpay_label_code" => "CO21",
                                    "wpay_label_desc" => "Saved Card feature will be "
                                    . "available only if enabled by Merchant.",
                                    "wpay_custom_label" => ""],
                                $index.'_21' => ["wpay_label_code" => "CO22",
                                    "wpay_label_desc" => "Expiration Date",
                                    "wpay_custom_label" => ""],
                                $index.'_22' => ["wpay_label_code" => "CO23",
                                    "wpay_label_desc" => "Disclaimer!",
                                    "wpay_custom_label" => ""],
                                $index.'_23' => ["wpay_label_code" => "CO24",
                                    "wpay_label_desc" => "Card Verification Number",
                                    "wpay_custom_label" => ""],
                                $index.'_24' => ["wpay_label_code" => "CO25",
                                    "wpay_label_desc" => "Saved cards",
                                    "wpay_custom_label" => ""],
                            ];
        $labelsCodes = $this->convertArrayToStringForLabels($labelsValue);
        $configData = [
            'section' => 'worldpay_custom_labels',
            'website' => null,
            'store'   => null,
            'groups'  => [
                'checkout_labels' => [
                    'fields' => [
                        'checkout_label' => [
                            'value' => $labelsCodes
                        ],
                    ],
                ],
            ],
        ];
        /** @var \Magento\Config\Model\Config $configModel */
        $configModel = $this->configFactory->create(['data' => $configData]);
        $configModel->save();
    }
        /**
         *  Save Myaccount Labels
         */
    public function saveMyAccountLabels()
    {
        $index = time();
        $labelsValue = [ $index.'_0' => ["wpay_label_code" => "AC2",
                                    "wpay_label_desc" => "Card Brand #",
                                    "wpay_custom_label" => ""],
                                $index.'_1' => ["wpay_label_code" => "AC3",
                                    "wpay_label_desc" => "Card Number",
                                    "wpay_custom_label" => ""],
                                $index.'_2' => ["wpay_label_code" => "AC4",
                                    "wpay_label_desc" => "Card Expiry Month",
                                    "wpay_custom_label" => ""],
                                $index.'_3' => ["wpay_label_code" => "AC5",
                                    "wpay_label_desc" => "Card Expiry Year",
                                    "wpay_custom_label" => ""],
                                $index.'_4' => ["wpay_label_code" => "AC6",
                                    "wpay_label_desc" => "Update",
                                    "wpay_custom_label" => ""],
                                $index.'_5' => ["wpay_label_code" => "AC7",
                                    "wpay_label_desc" => "Update Saved Card",
                                    "wpay_custom_label" => ""],
                                $index.'_6' => ["wpay_label_code" => "AC8",
                                    "wpay_label_desc" => "Card Information",
                                    "wpay_custom_label" => ""],
                                $index.'_7' => ["wpay_label_code" => "AC10",
                                    "wpay_label_desc" => "Expiry Month/Year",
                                    "wpay_custom_label" => ""],
                                $index.'_8' => ["wpay_label_code" => "AC11",
                                    "wpay_label_desc" => "Delete",
                                    "wpay_custom_label" => ""],
                                $index.'_9' => ["wpay_label_code" => "AC12",
                                    "wpay_label_desc" => "My Subscriptions",
                                    "wpay_custom_label" => ""],
                                $index.'_10' => ["wpay_label_code" => "AC13",
                                    "wpay_label_desc" => "Original Order #",
                                    "wpay_custom_label" => ""],
                                $index.'_11' => ["wpay_label_code" => "AC14",
                                    "wpay_label_desc" => "Original Order Date",
                                    "wpay_custom_label" => ""],
                                $index.'_12' => ["wpay_label_code" => "AC15",
                                    "wpay_label_desc" => "Product",
                                    "wpay_custom_label" => ""],
                                $index.'_13' => ["wpay_label_code" => "AC16",
                                    "wpay_label_desc" => "Amount",
                                    "wpay_custom_label" => ""],
                                $index.'_14' => ["wpay_label_code" => "AC17",
                                    "wpay_label_desc" => "Interval",
                                    "wpay_custom_label" => ""],
                                $index.'_15' => ["wpay_label_code" => "AC18",
                                    "wpay_label_desc" => "Start Date",
                                    "wpay_custom_label" => ""],
                                $index.'_16' => ["wpay_label_code" => "AC19",
                                    "wpay_label_desc" => "End Date",
                                    "wpay_custom_label" => ""],
                                $index.'_17' => ["wpay_label_code" => "AC20",
                                    "wpay_label_desc" => "Status",
                                    "wpay_custom_label" => ""],
                                $index.'_18' => ["wpay_label_code" => "AC21",
                                    "wpay_label_desc" => "Actions",
                                    "wpay_custom_label" => ""],
                                $index.'_19' => ["wpay_label_code" => "AC22",
                                    "wpay_label_desc" => "Edit",
                                    "wpay_custom_label" => ""],
                                $index.'_20' => ["wpay_label_code" => "AC23",
                                    "wpay_label_desc" => "Cancel",
                                    "wpay_custom_label" => ""],
                                $index.'_21' => ["wpay_label_code" => "AC24",
                                    "wpay_label_desc" => "Edit Subscription",
                                    "wpay_custom_label" => ""],
                                $index.'_22' => ["wpay_label_code" => "AC25",
                                    "wpay_label_desc" => "Subscription Information",
                                    "wpay_custom_label" => ""],
                                $index.'_23' => ["wpay_label_code" => "AC26",
                                    "wpay_label_desc" => "Product Name",
                                    "wpay_custom_label" => ""],
                                $index.'_24' => ["wpay_label_code" => "AC27",
                                    "wpay_label_desc" => "Payment Plan",
                                    "wpay_custom_label" => ""],
                                $index.'_25' => ["wpay_label_code" => "AC28",
                                    "wpay_label_desc" => "Save Subscription",
                                    "wpay_custom_label" => ""],
                                $index.'_26' => ["wpay_label_code" => "AC29",
                                    "wpay_label_desc" => "My Saved Card",
                                    "wpay_custom_label" => ""],
                                $index.'_27' => ["wpay_label_code" => "AC30",
                                    "wpay_label_desc" => "You have no Saved Card.",
                                    "wpay_custom_label" => ""],
                                $index.'_28' => ["wpay_label_code" => "AC31",
                                    "wpay_label_desc" => "Billing Information",
                                    "wpay_custom_label" => ""],
                                $index.'_29' => ["wpay_label_code" => "AC32",
                                    "wpay_label_desc" => "First Name",
                                    "wpay_custom_label" => ""],
                                $index.'_30' => ["wpay_label_code" => "AC33",
                                    "wpay_label_desc" => "Last Name",
                                    "wpay_custom_label" => ""],
                                $index.'_31' => ["wpay_label_code" => "AC34",
                                    "wpay_label_desc" => "Address",
                                    "wpay_custom_label" => ""],
                                $index.'_32' => ["wpay_label_code" => "AC35",
                                    "wpay_label_desc" => "City",
                                    "wpay_custom_label" => ""],
                                $index.'_33' => ["wpay_label_code" => "AC36",
                                    "wpay_label_desc" => "Zip/Postal Code",
                                    "wpay_custom_label" => ""],
                                $index.'_34' => ["wpay_label_code" => "AC37",
                                    "wpay_label_desc" => "Country",
                                    "wpay_custom_label" => ""],
                                $index.'_35' => ["wpay_label_code" => "AC38",
                                    "wpay_label_desc" => "State/Province",
                                    "wpay_custom_label" => ""],
                                $index.'_36' => ["wpay_label_code" => "AC39",
                                    "wpay_label_desc" => "Save",
                                    "wpay_custom_label" => ""],
                            ];
        $labelsCodes = $this->convertArrayToStringForLabels($labelsValue);
        $configData = [
            'section' => 'worldpay_custom_labels',
            'website' => null,
            'store'   => null,
            'groups'  => [
                'my_account_labels' => [
                    'fields' => [
                        'my_account_label' => [
                            'value' => $labelsCodes
                        ],
                    ],
                ],
            ],
        ];
        /** @var \Magento\Config\Model\Config $configModel */
        $configModel = $this->configFactory->create(['data' => $configData]);
        $configModel->save();
    }
        /**
         *  Save Admin Labels
         */
    public function saveAdminLabels()
    {

        $index = time();
        $labelsValue = [ $index.'_0' => ["wpay_label_code" => "AD3",
                                    "wpay_label_desc" => "Payment Plans",
                                    "wpay_custom_label" => ""],
                                $index.'_1' => ["wpay_label_code" => "AD4",
                                    "wpay_label_desc" => "Code",
                                    "wpay_custom_label" => ""],
                                $index.'_2' => ["wpay_label_code" => "AD5",
                                    "wpay_label_desc" => "Description",
                                    "wpay_custom_label" => ""],
                                $index.'_3' => ["wpay_label_code" => "AD6",
                                    "wpay_label_desc" => "Recurring Cycle",
                                    "wpay_custom_label" => ""],
                                $index.'_4' => ["wpay_label_code" => "AD7",
                                    "wpay_label_desc" => "Recurring Amount",
                                    "wpay_custom_label" => ""],
                                $index.'_5' => ["wpay_label_code" => "AD8",
                                    "wpay_label_desc" => "Website",
                                    "wpay_custom_label" => ""],
                                $index.'_6' => ["wpay_label_code" => "AD9",
                                    "wpay_label_desc" => "Active",
                                    "wpay_custom_label" => ""],
                                $index.'_7' => ["wpay_label_code" => "AD10",
                                    "wpay_label_desc" => "Add Payment Plan",
                                    "wpay_custom_label" => ""],
                                $index.'_8' => ["wpay_label_code" => "AD12",
                                    "wpay_label_desc" => "New Payment Plan",
                                    "wpay_custom_label" => ""],
                                $index.'_9' => ["wpay_label_code" => "AD13",
                                    "wpay_label_desc" => "Cancel",
                                    "wpay_custom_label" => ""],
                                $index.'_10' => ["wpay_label_code" => "AD14",
                                    "wpay_label_desc" => "Save Plan",
                                    "wpay_custom_label" => ""],
                            ];
        $labelsCodes = $this->convertArrayToStringForLabels($labelsValue);
        $configData = [
            'section' => 'worldpay_custom_labels',
            'website' => null,
            'store'   => null,
            'groups'  => [
                'admin_labels' => [
                    'fields' => [
                        'admin_label' => [
                            'value' => $labelsCodes
                        ],
                    ],
                ],
            ],
        ];
        /** @var \Magento\Config\Model\Config $configModel */
        $configModel = $this->configFactory->create(['data' => $configData]);
        $configModel->save();
    }
        /**
         *  Save Klarna Config
         */
    public function saveKlarnaConfig()
    {
        $index = time();
        $subscriptionConfigs= [ $index.'_0' => ["worldpay_klarna_subscription" => "SE",
                                        "subscription_days" => "14"],
                                $index.'_1' => ["worldpay_klarna_subscription" => "NO",
                                        "subscription_days" => "14"],
                                $index.'_2' => ["worldpay_klarna_subscription" => "FI",
                                        "subscription_days" => "14"],
                                $index.'_3' => ["worldpay_klarna_subscription" => "DE",
                                        "subscription_days" => "14"],
                                $index.'_4' => ["worldpay_klarna_subscription" => "AT",
                                        "subscription_days" => "14"],
                                $index.'_5' => ["worldpay_klarna_subscription" => "GB",
                                        "subscription_days" => "30"],
                                $index.'_6' => ["worldpay_klarna_subscription" => "DK",
                                        "subscription_days" => "14"],
                                $index.'_7' => ["worldpay_klarna_subscription" => "US",
                                        "subscription_days" => "30"],
                                $index.'_8' => ["worldpay_klarna_subscription" => "NL",
                                        "subscription_days" => "14"],
                                $index.'_9' => ["worldpay_klarna_subscription" => "CH",
                                        "subscription_days" => "14"],
                            ];
        $resultArray = [];
        foreach ($subscriptionConfigs as $row) {
            $payment_type = $row['worldpay_klarna_subscription'];
            $rs['subscription_days'] = $row['subscription_days'];
            $resultArray[$payment_type] = $rs;
        }
        $subscriptionConfigData= $this->serializer->serialize($resultArray);

        $configData = [
            'section' => 'worldpay',
            'website' => null,
            'store'   => null,
        'groups'  => [
                'klarna_config'=>['klarna_countries_config' => [
                    'fields' => [
                        'klarna_contries' => [
                        'value' => [
                                $index.'_0' => "AT,CH,NO,DE,DK,US,FI,GB,NL,SE",
                            ]

                        ],
                    ],
                    ]
                ],
                'klarna_config' =>['sliceit_config' => [
                    'fields' => [
                        'sliceit_contries' => [
                            'value' => [
                                $index.'_0' => "SE,NO,FI,DE,AT,GB,DK,US",
                            ]

                        ],
                    ],
                    ]
                ],
                'klarna_config' =>['paylater_config' => [
                    'fields' => [
                        'paylater_contries' => [
                            'value' => [
                                $index.'_0' => "SE,NO,FI,DE,NL,AT,CH,GB,DK,US",
                            ]

                        ],
                    ],
                    ]
                ],
                'klarna_config'=>['paynow_config' => [
                    'fields' => [
                        'paynow_contries' => [
                            'value' => [
                                $index.'_0' => "SE,DE,NL,AT",
                            ]

                        ],
                    ],
                    ]
                ],
                'klarna_config'=>['paylater_config'=>['paylater_days_config' => [
                    'fields' => [
                        'subscription_days' => [
                            'value' => $subscriptionConfigData
                        ],
                    ],
                ],
                ],
                    ]
                ]
        ];
        /** @var \Magento\Config\Model\Config $configModel */
        $configModel = $this->configFactory->create(['data' => $configData]);
        $configModel->save();
    }

    /**
     * Add Extra checkout Labels
     */
    public function addExtraCheckoutLabels()
    {
        $index = time();
        $configFactory = $this->configFactory->create();
        $configValues = $configFactory->getConfigDataValue('worldpay_custom_labels/checkout_labels/checkout_label');
        $labelsValue = [];
        if ($configValues) {
            $previousValues = $this->serializer->unserialize($configValues);
            if (is_array($previousValues)) {
                $resultArray = [];
                $counter = 0;
                foreach ($previousValues as $key => $row) {
                    $resultArray[$counter] = [
                        'wpay_label_code' => $key,
                        'wpay_label_desc'=> $row['wpay_label_desc'],
                        'wpay_custom_label'=> $row['wpay_custom_label']
                    ];
                    $counter++;
                }
                $iframeCheckOutMsg = "Please do not navigate away or refresh this page before ";
                $iframeCheckOutMsg .= "completing the payment or else this order will be cancelled";
                $newLabels = [
                    [
                        'wpay_label_code' => 'CO26',
                        'wpay_label_desc'=> 'Save this card for future usage and recurring payments',
                        'wpay_custom_label'=> ''
                    ],
                    [
                        'wpay_label_code' => 'CO27',
                        'wpay_label_desc'=> "Continue to Worldpay",
                        'wpay_custom_label'=> ""
                    ],
                    [
                        'wpay_label_code' => 'CO28',
                        'wpay_label_desc'=> $iframeCheckOutMsg,
                        'wpay_custom_label'=> ""
                    ],
                    [
                        'wpay_label_code' => 'CO29',
                        'wpay_label_desc'=> 'Mandate Type',
                        'wpay_custom_label'=> ''
                    ],
                    [
                        'wpay_label_code' => 'CO30',
                        'wpay_label_desc'=> 'IBAN',
                        'wpay_custom_label'=> ''
                    ],
                    [
                        'wpay_label_code' => 'CO31',
                        'wpay_label_desc'=> "Account Holder Name",
                        'wpay_custom_label'=> ''
                    ]
                ];
                foreach ($newLabels as $label) {
                    $resultArray[$counter] = $label;
                    $counter++;
                }
                $labelsValue = $resultArray;
            }
            $labelsCodes = $this->convertArrayToStringForLabels($labelsValue);
            $configData = [
                'section' => 'worldpay_custom_labels',
                'website' => null,
                'store'   => null,
                'groups'  => [
                    'checkout_labels' => [
                        'fields' => [
                            'checkout_label' => [
                                'value' => $labelsCodes
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
}
