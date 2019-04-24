<?php
namespace Boxalino\Intelligence\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use \Magento\Framework\DB\Adapter\AdapterInterface;
use \Magento\Framework\DB\Ddl\Table;

/**
 * Create table for boxalino exports tracker
 *
 * @package     Boxalino\Intelligence\
 * @author      Dana Negrescu <dana.negrescu@boxalino.com>
 */
class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * Upgrades DB schema for a module
     *
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();

        if (!$installer->tableExists('boxalino_export')
            && version_compare($context->getVersion(), '1.0.2', '<')) {
            $this->addExportTable($installer);
        }

        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            $this->addEntityIdsToExportTable($installer);
        }

        $installer->endSetup();
    }

    public function addExportTable(SchemaSetupInterface $installer)
    {
        if (!$installer->tableExists('boxalino_export')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('boxalino_export')
            )->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'nullable' => false,
                    'primary' => true,
                    'unsigned' => true,
                ],
                'ID'
            )->addColumn(
                'indexer_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [],
                'Indexer Id'
            )->addColumn(
                'updated',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => true],
                'Updated at'
            )->addIndex(
                $installer->getIdxName('boxalino_export', ['indexer_id']),
                ['indexer_id'],
                ['type'=> AdapterInterface::INDEX_TYPE_UNIQUE]
            )->setComment('Boxalino Exports Time tracker');

            $installer->getConnection()->createTable($table);
        }
    }

    /**
     * adding a new row to keep track of product changes via other events than product save
     *
     * @param SchemaSetupInterface $installer
     */
    public function addEntityIdsToExportTable(SchemaSetupInterface $installer)
    {
        $installer->startSetup();

        $connection = $installer->getConnection();
        if($connection->tableColumnExists($installer->getTable('boxalino_export'), "entity_id")){
            return $this;
        }
        
        $connection->addColumn(
            $installer->getTable('boxalino_export'),
            'entity_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'comment' => 'for delta exports: list of product IDs that have to be updated on next indexer run',
            ]
        );

        $installer->endSetup();
    }
}