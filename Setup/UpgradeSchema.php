<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface {

    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context )
    {
        $installer = $setup;
        $installer->startSetup();
        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('worldpay_notification_history')
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
                    $installer->getIdxName('worldpay_notification_history', ['order_id']),
                    ['order_id']
            )
            ->setComment('Worldpay Notification History')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
                        $installer->getConnection()->createTable($table);
        }

        $setup->getConnection()->changeColumn(
            $setup->getTable('worldpay_notification_history'),
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
            $installer->getTable('worldpay_payment'),
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
            $installer->getTable('worldpay_payment'),
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
            $installer->getTable('worldpay_payment'),
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
            $installer->getTable('worldpay_payment'),
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
            $installer->getTable('worldpay_payment'),
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
            $installer->getTable('worldpay_payment'),
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
            $installer->getTable('worldpay_payment'),
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


}
