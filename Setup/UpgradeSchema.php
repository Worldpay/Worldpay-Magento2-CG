<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface {

    const WORLDPAY_NOTIFICATION_HISTORY = 'worldpay_notification_history';
    const WORLDPAY_PAYMENT = 'worldpay_payment';
    const WORLDPAY_TOKEN = 'worldpay_token';

    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context )
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
}
