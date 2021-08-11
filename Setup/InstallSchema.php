<?php
/**
 * Ebrook
 *
 * @category    Ebrook
 * @package     AnyPlaceMedia_SendSMS
 * @copyright   Copyright © 2021 Ebrook co., ltd. (https://www.ebrook.com.tw)
 * @source https://github.com/sendSMS-RO/sendsms-magento2.4
 */

namespace AnyPlaceMedia\SendSMS\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $table = $installer->getConnection()
            ->newTable($installer->getTable('sendsms_history'))
            ->addColumn(
                'history_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'History ID'
            )
            ->addColumn('status', Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => null])
            ->addColumn('message', Table::TYPE_TEXT, 255, ['nullable' => true])
            ->addColumn('details', Table::TYPE_TEXT, '2G', ['nullable' => true])
            ->addColumn('content', Table::TYPE_TEXT, '2G', ['nullable' => true])
            ->addColumn('type', Table::TYPE_TEXT, 255, ['nullable' => true])
            ->addColumn('sent_on', Table::TYPE_DATETIME, null, ['nullable' => false])
            ->addColumn('phone', Table::TYPE_TEXT, 255, ['nullable' => true]);

        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
