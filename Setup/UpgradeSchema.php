<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    const WORLDPAY_NOTIFICATION_HISTORY = 'worldpay_notification_history';
    const WORLDPAY_PAYMENT = 'worldpay_payment';
    const WORLDPAY_TOKEN = 'worldpay_token';
    const WORLDPAY_RECURRING_PLANS = 'worldpay_recurring_plans';
    const WORLDPAY_SUBSCRIPTIONS = 'worldpay_subscriptions';
    const WORLDPAY_SUBSCRIPTIONS_ADDRESS = 'worldpay_subscription_address';
    const WORLDPAY_RECURRING_TRANSACTIONS = 'worldpay_recurring_transactions';

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable(self::WORLDPAY_NOTIFICATION_HISTORY)
            )
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [
                        'identity' => true,
                        'nullable' => false,
                        'primary'  => true,
                        'unsigned' => true,
                    ],
                'Id'
            )
            ->addColumn(
                'order_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false,
                    'unsigned' => true],
                'Order Id'
            )
            ->addColumn(
                'status',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [],
                'Status'
            )
            ->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Created At'
            )
            ->addIndex(
                $installer->getIdxName(self::WORLDPAY_NOTIFICATION_HISTORY, ['order_id']),
                ['order_id']
            )
            ->setComment('Worldpay Notification History')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
                        $installer->getConnection()->createTable($table);
        }

        $setup->getConnection()->changeColumn(
            $setup->getTable(self::WORLDPAY_NOTIFICATION_HISTORY),
            'order_id',
            'order_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Worldpay order id'
            ]
        );
        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $this->addColumnWP($installer);
        }
        if (version_compare($context->getVersion(), '1.2.2', '<')) {
            $this->addColumnCse($installer);
        }
        if (version_compare($context->getVersion(), '1.2.3', '<')) {
            $this->addColumnToken($installer);
        }
        if (version_compare($context->getVersion(), '1.2.4', '<')) {
            $this->addColumnBin($installer);
        }
        if (version_compare($context->getVersion(), '1.2.5', '<')) {
            $this->modifyColumnOrderId($installer);
        }
        if (version_compare($context->getVersion(), '1.2.6', '<')) {
            $this->addColumnDisclaimer($installer);
        }
        if (version_compare($context->getVersion(), '1.2.7', '<')) {
            $this->createRecurringPlansTable($installer);
        }
        if (version_compare($context->getVersion(), '1.2.8', '<')) {
            $this->createSubscriptionsTable($installer);
        }
        if (version_compare($context->getVersion(), '1.2.9', '<')) {
            $this->addSubscriptionIdToOrder($installer);
        }
        if (version_compare($context->getVersion(), '1.3.0', '<')) {
            $this->createSubscriptionAddressTable($installer);
        }
        if (version_compare($context->getVersion(), '1.3.1', '<')) {
            $this->createRecurringTransactionsTable($installer);
        }
        if (version_compare($context->getVersion(), '1.3.2', '<')) {
            $this->addColumnTokenTypeToWorldpayToken($installer);
        }
        if (version_compare($context->getVersion(), '1.3.3', '<')) {
            $this->addColumnLatAmInstalments($installer);
        }
        $installer->endSetup();
    }
    /**
     * @param SchemaSetupInterface $installer
     * @return void
     */
    private function addColumnCse(SchemaSetupInterface $installer)
    {
        $connection = $installer->getConnection();
        $connection->addColumn(
            $installer->getTable(self::WORLDPAY_PAYMENT),
            'client_side_encryption',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                'nullable' => false,
                'comment' => 'Client side encryption',
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $installer
     * @return void
     */
    private function addColumnWP(SchemaSetupInterface $installer)
    {
        $connection = $installer->getConnection();
        $connection->addColumn(
            $installer->getTable(self::WORLDPAY_PAYMENT),
            'aav_address_result_code',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'length' => '25',
                'comment' => 'AAV Address Result Code',
                'after' => 'risk_provider_final'
            ]
        );

        $connection->addColumn(
            $installer->getTable(self::WORLDPAY_PAYMENT),
            'avv_postcode_result_code',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'length' => '25',
                'comment' => 'AAV Postcode Result Code',
                'after' => 'aav_address_result_code'
            ]
        );

        $connection->addColumn(
            $installer->getTable(self::WORLDPAY_PAYMENT),
            'aav_cardholder_name_result_code',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'length' => '25',
                'comment' => 'AAV Cardholder Name Result Code',
                'after' => 'avv_postcode_result_code'
            ]
        );

        $connection->addColumn(
            $installer->getTable(self::WORLDPAY_PAYMENT),
            'aav_telephone_result_code',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'length' => '25',
                'comment' => 'AAV Telephone Result Code',
                'after' => 'aav_cardholder_name_result_code'
            ]
        );
        $connection->addColumn(
            $installer->getTable(self::WORLDPAY_PAYMENT),
            'aav_email_result_code',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'length' => '25',
                'comment' => 'AAV Email Result Code',
                'after' => 'aav_telephone_result_code'
            ]
        );

        $connection->addColumn(
            $installer->getTable(self::WORLDPAY_PAYMENT),
            'interaction_type',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'length' => '25',
                'comment' => 'Interaction Type',
                'after' => 'aav_email_result_code'
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $installer
     * @return void
     */
    private function addColumnToken(SchemaSetupInterface $installer)
    {
        $connection = $installer->getConnection();
        $connection->addColumn(
            $installer->getTable(self::WORLDPAY_TOKEN),
            'transaction_identifier',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Transaction Indentifier',
                'after' => 'authenticated_shopper_id'
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $installer
     * @return void
     */
    private function addColumnBin(SchemaSetupInterface $installer)
    {
        $connection = $installer->getConnection();
        $connection->addColumn(
            $installer->getTable(self::WORLDPAY_TOKEN),
            'bin_number',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'length' => '25',
                'comment' => 'Bin Number',
                'before' => 'created_at'
            ]
        );
    }
    
    /**
     * @param SchemaSetupInterface $installer
     * @return void
     */
    private function modifyColumnOrderId(SchemaSetupInterface $installer)
    {
        $connection = $installer->getConnection();
        $connection->modifyColumn(
            $installer->getTable(self::WORLDPAY_PAYMENT),
            'order_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 25,
                'nullable' => false,
                'unsigned' => true,
                'comment' => 'Order Id'
            ]
        );
    }
    
    /**
     * @param SchemaSetupInterface $installer
     * @return void
     */
    private function addColumnDisclaimer(SchemaSetupInterface $installer)
    {
        $connection = $installer->getConnection();
        $connection->addColumn(
            $installer->getTable(self::WORLDPAY_TOKEN),
            'disclaimer_flag',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                'nullable' => false,
                'comment' => 'Disclaimer Flag',
                'before' => 'created_at'
            ]
        );
    }
    
    /**
     * Create Recurring Plans table
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function createRecurringPlansTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        /**
         * Create table 'worldpay_recurring_plans'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::WORLDPAY_RECURRING_PLANS)
        )->addColumn(
            'plan_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'unsigned' => true, 'primary' => true],
            'Plan ID'
        )->addColumn(
            'product_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'unsigned' => true],
            'Product ID'
        )->addColumn(
            'website_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false, 'unsigned' => true, 'default' => '0'],
            'Website ID'
        )->addColumn(
            'code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Code'
        )->addColumn(
            'name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            100,
            [],
            'Name'
        )->addColumn(
            'description',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            100,
            [],
            'Description'
        )->addColumn(
            'number_of_payments',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Number of Payments'
        )->addColumn(
            'interval',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Interval'
        )->addColumn(
            'interval_amount',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['unsigned' => true],
            'Interval Amount'
        )->addColumn(
            'trial_interval',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Trial Interval'
        )->addColumn(
            'number_of_trial_intervals',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Number of Trial Intervals'
        )->addColumn(
            'sort_order',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false, 'default' => '0'],
            'Sort Order'
        )->addColumn(
            'active',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'default' => '0'],
            'Active'
        )->addIndex(
            $installer->getIdxName(self::WORLDPAY_RECURRING_PLANS, ['code']),
            ['code']
        )->addIndex(
            $installer->getIdxName(self::WORLDPAY_RECURRING_PLANS, ['active', 'sort_order']),
            ['active', 'sort_order']
        )->addForeignKey(
            $installer->getFkName(self::WORLDPAY_RECURRING_PLANS, 'product_id', 'catalog_product_entity', 'entity_id'),
            'product_id',
            $installer->getTable('catalog_product_entity'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->addForeignKey(
            $installer->getFkName(self::WORLDPAY_RECURRING_PLANS, 'website_id', 'store_website', 'website_id'),
            'website_id',
            $installer->getTable('store_website'),
            'website_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Worldpay Recurring Plans'
        );
        $installer->getConnection()->createTable($table);

        return $this;
    }
    
    /**
     * Create subscriptions table
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function createSubscriptionsTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        /**
         * Create table 'worldpay_subscriptions'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::WORLDPAY_SUBSCRIPTIONS)
        )->addColumn(
            'subscription_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            10,
            ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
            'Subscription ID'
        )->addColumn(
            'store_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true],
            'Store Id'
        )->addColumn(
            'store_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Store Name'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Created At'
        )->addColumn(
            'updated_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
            'Updated At'
        )->addColumn(
            'plan_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Plan ID'
        )->addColumn(
            'interval_amount',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['unsigned' => true],
            'Interval Amount'
        )->addColumn(
            'start_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            null,
            [],
            'Start Date'
        )->addColumn(
            'worldpay_subscription_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            20,
            ['nullable' => false],
            'Worldpay Subscription ID'
        )->addColumn(
            'customer_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Customer ID'
        )->addColumn(
            'original_order_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Original Order ID'
        )->addColumn(
            'original_order_increment_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Original Order Increment ID'
        )->addColumn(
            'product_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Product ID'
        )->addColumn(
            'product_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Product Name'
        )->addColumn(
            'billing_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Billing Name'
        )->addColumn(
            'shipping_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Shipping Name'
        )->addColumn(
            'customer_email',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            128,
            [],
            'Customer Email'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Status'
        )->addColumn(
            'shipping_method',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Shipping Method'
        )->addColumn(
            'shipping_description',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Shipping Description'
        )->addColumn(
            'is_virtual',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true],
            'Is Virtual'
        )->addColumn(
            'discount_amount',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['unsigned' => true],
            'Discount Amount'
        )->addColumn(
            'discount_description',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Discount Description'
        )->addColumn(
            'shipping_amount',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['unsigned' => true],
            'Shipping Amount'
        )->addColumn(
            'shipping_tax_amount',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['unsigned' => true],
            'Shipping Tax Amount'
        )->addColumn(
            'subtotal',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['unsigned' => true],
            'Order Subtotal'
        )->addColumn(
            'tax_amount',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['unsigned' => true],
            'Tax Amount'
        )->addColumn(
            'subtotal_incl_tax',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['unsigned' => true],
            'Subtotal Incl Tax'
        )->addColumn(
            'weight',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['unsigned' => true],
            'Weight'
        )->addColumn(
            'customer_note',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            64000,
            [],
            'Customer Note'
        )->addColumn(
            'product_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Product Type'
        )->addColumn(
            'product_options',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            64000,
            [],
            'Product Options'
        )->addColumn(
            'product_sku',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Product SKU'
        )->addColumn(
            'item_price',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['default' => '0.0000'],
            'Item Price'
        )->addColumn(
            'item_original_price',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['default' => '0.0000'],
            'Item Original Price'
        )->addColumn(
            'item_tax_percent',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['default' => '0.0000'],
            'Item Tax Percent'
        )->addColumn(
            'item_tax_amount',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['default' => '0.0000'],
            'Item Tax Amount'
        )->addColumn(
            'item_discount_percent',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['default' => '0.0000'],
            'Item Discount Percent'
        )->addColumn(
            'item_discount_amount',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['default' => '0.0000'],
            'Item Discount Amount'
        )->addColumn(
            'payment_method',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            128,
            [],
            'Payment Method'
        )->addColumn(
            'payment_cc_exp_month',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            12,
            [],
            'Payment Cc Exp Month'
        )->addColumn(
            'payment_cc_exp_year',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            12,
            [],
            'Payment Cc Exp Year'
        )->addColumn(
            'payment_cc_last_4',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            100,
            [],
            'Payment Cc Last 4'
        )->addColumn(
            'payment_additional_information',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            64000,
            [],
            'Payment Additional Information'
        )->addIndex(
            $installer->getIdxName(self::WORLDPAY_SUBSCRIPTIONS, ['status']),
            ['status']
        )->addIndex(
            $installer->getIdxName(self::WORLDPAY_SUBSCRIPTIONS, ['worldpay_subscription_id']),
            ['worldpay_subscription_id']
        )->addIndex(
            $installer->getIdxName(self::WORLDPAY_SUBSCRIPTIONS, ['store_id']),
            ['store_id']
        )->addIndex(
            $installer->getIdxName(self::WORLDPAY_SUBSCRIPTIONS, ['customer_id', 'store_id']),
            ['customer_id', 'store_id']
        )->addIndex(
            $installer->getIdxName(self::WORLDPAY_SUBSCRIPTIONS, ['original_order_increment_id']),
            ['original_order_increment_id']
        )->addIndex(
            'INDEX_KEY',
            ['original_order_increment_id', 'product_name', 'billing_name', 'shipping_name'],
            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT]
        )->addForeignKey(
            $installer->getFkName(self::WORLDPAY_SUBSCRIPTIONS, 'store_id', 'store', 'store_id'),
            'store_id',
            $installer->getTable('store'),
            'store_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        )->addForeignKey(
            $installer->getFkName(self::WORLDPAY_SUBSCRIPTIONS, 'plan_id', self::WORLDPAY_RECURRING_PLANS, 'plan_id'),
            'plan_id',
            $installer->getTable(self::WORLDPAY_RECURRING_PLANS),
            'plan_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        )->addForeignKey(
            $installer->getFkName(self::WORLDPAY_SUBSCRIPTIONS, 'customer_id', 'customer_entity', 'entity_id'),
            'customer_id',
            $installer->getTable('customer_entity'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        )->addForeignKey(
            $installer->getFkName(self::WORLDPAY_SUBSCRIPTIONS, 'original_order_id', 'sales_order', 'entity_id'),
            'original_order_id',
            $installer->getTable('sales_order'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        )->addForeignKey(
            $installer->getFkName(self::WORLDPAY_SUBSCRIPTIONS, 'product_id', 'catalog_product_entity', 'entity_id'),
            'product_id',
            $installer->getTable('catalog_product_entity'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        )->setComment(
            'Worldpay Subscriptions'
        );
        $installer->getConnection()->createTable($table);

        return $this;
    }
    
    /**
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function addSubscriptionIdToOrder(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        $connection = $installer->getConnection();
        $connection->addColumn(
            $installer->getTable('sales_order'),
            'worldpay_subscription_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'unsigned' => true,
                'nullable' => true,
                'comment' => 'Worldpay Subscription ID'
            ]
        );

        $connection->addIndex(
            $installer->getTable('sales_order'),
            $installer->getIdxName('sales_order', 'worldpay_subscription_id'),
            'worldpay_subscription_id'
        );

        $connection->addColumn(
            $installer->getTable('sales_order_grid'),
            'worldpay_subscription_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'unsigned' => true,
                'nullable' => true,
                'comment' => 'Worldpay Subscription ID'
            ]
        );

        $connection->addIndex(
            $installer->getTable('sales_order_grid'),
            $installer->getIdxName('sales_order_grid', 'worldpay_subscription_id'),
            'worldpay_subscription_id'
        );

        return $this;
    }
    
    /**
     * Create subscription address table
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function createSubscriptionAddressTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        /**
         * Create table 'worldpay_subscription_address'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::WORLDPAY_SUBSCRIPTIONS_ADDRESS)
        )->addColumn(
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Entity Id'
        )->addColumn(
            'subscription_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Subscription Id'
        )->addColumn(
            'region_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [],
            'Region Id'
        )->addColumn(
            'fax',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Fax'
        )->addColumn(
            'region',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Region'
        )->addColumn(
            'postcode',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Postcode'
        )->addColumn(
            'lastname',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Lastname'
        )->addColumn(
            'street',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Street'
        )->addColumn(
            'city',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'City'
        )->addColumn(
            'email',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Email'
        )->addColumn(
            'telephone',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Phone Number'
        )->addColumn(
            'country_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            2,
            [],
            'Country Id'
        )->addColumn(
            'firstname',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Firstname'
        )->addColumn(
            'address_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Address Type'
        )->addColumn(
            'prefix',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Prefix'
        )->addColumn(
            'middlename',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Middlename'
        )->addColumn(
            'suffix',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Suffix'
        )->addColumn(
            'company',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Company'
        )->addForeignKey(
            $installer->getFkName(
                self::WORLDPAY_SUBSCRIPTIONS_ADDRESS,
                'subscription_id',
                self::WORLDPAY_SUBSCRIPTIONS,
                'subscription_id'
            ),
            'subscription_id',
            $installer->getTable(self::WORLDPAY_SUBSCRIPTIONS),
            'subscription_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Subscription Address'
        );
        $installer->getConnection()->createTable($table);

        return $this;
    }
    
    /**
     * Create recurring transactions table
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function createRecurringTransactionsTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        /**
         * Create table 'worldpay_recurring_transactions'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::WORLDPAY_RECURRING_TRANSACTIONS)
        )->addColumn(
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Entity Id'
        )->addColumn(
            'original_order_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Original Order ID'
        )->addColumn(
            'original_order_increment_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Original Order Increment ID'
        )->addColumn(
            'customer_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Customer ID'
        )->addColumn(
            'plan_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Plan Id'
        )->addColumn(
            'subscription_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Subscription Id'
        )->addColumn(
            'recurring_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            null,
            [],
            'Recurring Date'
        )->addColumn(
            'recurring_end_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            null,
            [],
            'Recurring End Date'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Status'
        )->addColumn(
            'recurring_order_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Recurring Order ID'
        )->addColumn(
            'worldpay_token_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Worldpay token ID'
        )->addColumn(
            'worldpay_order_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '40',
            [],
            'WorldPay Order Id'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Created At'
        )->addColumn(
            'updated_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
            'Updated At'
        )->addIndex(
            $installer->getIdxName(self::WORLDPAY_RECURRING_TRANSACTIONS, ['status']),
            ['status']
        )->addIndex(
            $installer->getIdxName(self::WORLDPAY_RECURRING_TRANSACTIONS, ['subscription_id']),
            ['subscription_id']
        )->addIndex(
            $installer->getIdxName(self::WORLDPAY_RECURRING_TRANSACTIONS, ['customer_id']),
            ['customer_id']
        )->addIndex(
            $installer->getIdxName(self::WORLDPAY_RECURRING_TRANSACTIONS, ['original_order_id']),
            ['original_order_id']
        )->addIndex(
            $installer->getIdxName(self::WORLDPAY_RECURRING_TRANSACTIONS, ['original_order_increment_id']),
            ['original_order_increment_id']
        )->addIndex(
            $installer->getIdxName(self::WORLDPAY_RECURRING_TRANSACTIONS, ['worldpay_order_id']),
            ['worldpay_order_id']
        )->addForeignKey(
            $installer->getFkName(self::WORLDPAY_RECURRING_TRANSACTIONS, 'customer_id', 'customer_entity', 'entity_id'),
            'customer_id',
            $installer->getTable('customer_entity'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        )->addForeignKey(
            $installer->getFkName(
                self::WORLDPAY_RECURRING_TRANSACTIONS,
                'subscription_id',
                self::WORLDPAY_SUBSCRIPTIONS,
                'subscription_id'
            ),
            'subscription_id',
            $installer->getTable(self::WORLDPAY_SUBSCRIPTIONS),
            'subscription_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Recurring Transactions'
        );
        $installer->getConnection()->createTable($table);

        return $this;
    }
    
    private function addColumnLatAmInstalments(SchemaSetupInterface $installer)
    {
        $connection = $installer->getConnection();
        $connection->addColumn(
            $installer->getTable(self::WORLDPAY_PAYMENT),
            'latam_instalments',
            [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            'nullable' => true,
            'comment' => 'Latin America Instalments',
            'after' => 'interaction_type'
               ]
        );
    }
    /**
     * @param SchemaSetupInterface $installer
     * @return void
     */
    private function addColumnTokenTypeToWorldpayToken(SchemaSetupInterface $installer)
    {
        $connection = $installer->getConnection();
        $connection->addColumn(
            $installer->getTable(self::WORLDPAY_TOKEN),
            'token_type',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '32',
                [],
                'comment' => 'Token type',
                'before' => 'created_at'
            ]
        );
    }
}
