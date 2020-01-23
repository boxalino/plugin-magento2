<?php
namespace Boxalino\Intelligence\Model\ResourceModel;

use Boxalino\Intelligence\Api\ExporterResourceInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use \Psr\Log\LoggerInterface;
use \Magento\Framework\App\ResourceConnection;
use Magento\Framework\Config\ConfigOptionsListConstants;

/**
 * Keeps most of db access for the exporter class
 *
 * Class Exporter
 * @package Boxalino\Intelligence\Model\ResourceModel
 */
class Exporter implements ExporterResourceInterface
{

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $adapter;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\App\DeploymentConfig
     */
    protected $deploymentConfig;

    /**
     * @var []
     */
    protected $exportIds = [];

    /**
     * @var bool
     */
    protected $isDelta = false;

    /**
     * Exporter constructor.
     * @param LoggerInterface $logger
     * @param ResourceConnection $resource
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     */
    public function __construct(
        LoggerInterface $logger,
        ResourceConnection $resource,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig
    ) {
        $this->logger = $logger;
        $this->adapter = $resource->getConnection();
        $this->deploymentConfig = $deploymentConfig;
    }

    public function getProductAttributeValue($id, $attributeId, $storeId)
    {
        $select = $this->adapter->select()
            ->from(
                ['c_p_e' => $this->adapter->getTableName('catalog_product_entity')],
                [new \Zend_Db_Expr("CASE WHEN c_p_e_b.value IS NULL THEN c_p_e_a.value ELSE c_p_e_b.value END as value")]
            )
            ->joinLeft(
                ['c_p_e_a' => $this->adapter->getTableName('catalog_product_entity_int')],
                'c_p_e_a.entity_id = c_p_e.entity_id AND c_p_e_a.store_id = 0 AND c_p_e_a.attribute_id = ' . $attributeId,
                []
            )
            ->joinLeft(
                ['c_p_e_b' => $this->adapter->getTableName('catalog_product_entity_int')],
                'c_p_e_b.entity_id = c_p_e.entity_id AND c_p_e_b.store_id = ' . $storeId . ' AND c_p_e_b.attribute_id = ' . $attributeId,
                []
            )
            ->where('c_p_e.entity_id = ?', $id);

        return $this->adapter->fetchOne($select);
    }

    public function getProductDuplicateIds($storeId, $attributeId, $condition)
    {
        $select = $this->adapter->select()
            ->from(
                ['c_p_r' => $this->adapter->getTableName('catalog_product_relation')],
                [
                    'child_id',
                    new \Zend_Db_Expr("CASE WHEN c_p_e_b.value IS NULL THEN c_p_e_a.value ELSE c_p_e_b.value END as value")
                ]
            )->joinLeft(
                ['c_p_e_a' => $this->adapter->getTableName('catalog_product_entity_int')],
                'c_p_e_a.entity_id = c_p_r.child_id AND c_p_e_a.store_id = 0 AND c_p_e_a.attribute_id = ' . $attributeId,
                ['default_store'=>'c_p_e_a.store_id']
            )->joinLeft(
                ['c_p_e_b' => $this->adapter->getTableName('catalog_product_entity_int')],
                'c_p_e_b.entity_id = c_p_r.child_id AND c_p_e_b.store_id = ' . $storeId . ' AND c_p_e_b.attribute_id = ' . $attributeId,
                ['c_p_e_b.store_id']
            );

        if(!empty($this->exportIds) && $this->isDelta)
        {
            $select->where('c_p_r.parent_id IN(?)', $this->exportIds);
        }

        $main =  $this->adapter->select()
            ->from(
                ['main'=> new \Zend_Db_Expr('( '. $select->__toString() . ' )')],
                ['id'=>'child_id', 'child_id']
            )
            ->where('main.value <> ?', $condition);

        return $this->adapter->fetchPairs($main);

    }

    public function getAttributeIdByAttributeCodeAndEntityType($code, $type)
    {
        $whereConditions = [
            $this->adapter->quoteInto(
                'attr.attribute_code = ?',
                $code
            ),
            $this->adapter->quoteInto(
                'attr.entity_type_id = ?',
                $type
            )
        ];

        $attributeIdSql = $this->adapter->select()
            ->from(['attr'=>'eav_attribute'], ['attribute_id'])
            ->where(implode(' AND ', $whereConditions));

        return $this->adapter->fetchOne($attributeIdSql);
    }

    public function getCategoriesByStoreId($storeId)
    {
        $attributeId = $this->getAttributeIdByAttributeCodeAndEntityType('name', \Magento\Catalog\Setup\CategorySetup::CATEGORY_ENTITY_TYPE_ID);
        $select = $this->adapter->select()
            ->from(
                ['c_t' => $this->adapter->getTableName('catalog_category_entity')],
                ['entity_id', 'parent_id']
            )
            ->joinInner(
                ['c_v_i' => $this->adapter->getTableName('catalog_category_entity_varchar')],
                'c_v_i.entity_id = c_t.entity_id AND c_v_i.store_id = 0 AND c_v_i.attribute_id = ' . $attributeId,
                ['value_default'=>'c_v_i.value']
            )
            ->joinLeft(
                ['c_v_l' => $this->adapter->getTableName('catalog_category_entity_varchar')],
                'c_v_l.entity_id = c_t.entity_id AND c_v_l.attribute_id = ' . $attributeId,
                ['c_v_l.value', 'c_v_l.store_id']
            );

        $selectSql = $this->adapter->select()
            ->from(
                array('joins' => new \Zend_Db_Expr("( " . $select->__toString() . ")")),
                array(
                    'entity_id' => 'joins.entity_id',
                    'parent_id' => 'joins.parent_id',
                    new \Zend_Db_Expr("IF (joins.value IS NULL OR joins.value='', joins.value_default, joins.value ) AS value")
                )
            );

        return $this->adapter->fetchAll($selectSql);
    }

    public function getProductAttributes()
    {
        $select = $this->adapter->select()
            ->from(
                ['ca_t' => $this->adapter->getTableName('catalog_eav_attribute')],
                ['attribute_id']
            )
            ->joinInner(
                ['a_t' => $this->adapter->getTableName('eav_attribute')],
                'ca_t.attribute_id = a_t.attribute_id',
                ['attribute_code']
            );

        return $this->adapter->fetchPairs($select);
    }

    public function getCustomerAttributes()
    {
        $select = $this->adapter->select()
            ->from(
                ['a_t' => $this->adapter->getTableName('eav_attribute')],
                ['code' => 'attribute_code', 'attribute_code']
            )
            ->where('a_t.entity_type_id = ?', \Magento\Customer\Api\CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER);

        return $this->adapter->fetchPairs($select);
    }

    public function getCustomerAttributesByCodes($codes = [])
    {
        $select = $this->adapter->select()
            ->from(
                ['main_table' => $this->adapter->getTableName('eav_attribute')],
                [
                    'aid' => 'attribute_id',
                    'attribute_code',
                    'backend_type',
                ]
            )
            ->joinInner(
                ['additional_table' => $this->adapter->getTableName('customer_eav_attribute')],
                'additional_table.attribute_id = main_table.attribute_id',
                []
            )
            ->where('main_table.entity_type_id = ?', \Magento\Customer\Api\CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER)
            ->where('main_table.attribute_code IN (?)', $codes);

        return $this->adapter->fetchAll($select);
    }

    public function getCustomerAddressByFieldsAndLimit($limit, $page, $attributeGroups = [])
    {
        $select = $this->adapter->select()
            ->from(
                $this->adapter->getTableName('customer_entity'),
                $attributeGroups
            )
            ->join(
                $this->adapter->getTableName('customer_address_entity'),
                'customer_entity.entity_id = customer_address_entity.parent_id',
                ['country_id', 'postcode']
            )
            ->group('customer_entity.entity_id')
            ->limit($limit, ($page - 1) * $limit);

        return $this->adapter->fetchAll($select);
    }

    public function getUnionCustomerAttributesByAttributesAndIds($attributes, $ids)
    {
        $columns = ['entity_id', 'attribute_id', 'value'];
        $attributeTypes = ['varchar', 'int', 'datetime'];

        $selects = [];
        foreach($attributeTypes as $type)
        {
            if (count($attributes[$type]) > 0) {
                $selects[] = $this->getSqlForCustomerAttributesUnion($this->adapter->getTableName('customer_entity_'. $type), $columns, $attributes[$type], $ids);
            }
        }

        if(count($selects)) {
            $select = $this->adapter->select()
                ->union(
                    $selects,
                    \Magento\Framework\DB\Select::SQL_UNION_ALL
                );

            return $this->adapter->fetchAll($select);
        }
    }

    public function getProductEntityByLimitPage($limit, $page)
    {
        $select = $this->adapter->select()
            ->from(
                ['e' => $this->adapter->getTableName('catalog_product_entity')],
                ["*"]
            )
            ->limit($limit, ($page - 1) * $limit)
            ->joinLeft(
                ['p_t' => $this->adapter->getTableName('catalog_product_relation')],
                'e.entity_id = p_t.child_id', ['group_id' => 'parent_id']
            );

        if(!empty($this->exportIds) && $this->isDelta)
        {
            $select->where('e.entity_id IN(?)', $this->exportIds);
        }

        return $this->adapter->fetchAll($select);
    }

    public function getProductAttributesByCodes($codes = [])
    {
        $select = $this->adapter->select()
            ->from(
                ['main_table' => $this->adapter->getTableName('eav_attribute')],
                ['attribute_id', 'attribute_code', 'backend_type', 'frontend_input']
            )
            ->joinInner(
                ['additional_table' => $this->adapter->getTableName('catalog_eav_attribute'), 'is_global'],
                'additional_table.attribute_id = main_table.attribute_id'
            )
            ->where('main_table.entity_type_id = ?', \Magento\Catalog\Setup\CategorySetup::CATALOG_PRODUCT_ENTITY_TYPE_ID)
            ->where('main_table.attribute_code IN(?)', $codes);

        return $this->adapter->fetchAll($select);
    }

    protected function getSqlForCustomerAttributesUnion($table, $columns, $attributes, $ids)
    {
        return $this->adapter->select()
            ->from(['ce' => $table], $columns)
            ->joinLeft(
                ['ea' => $this->adapter->getTableName('eav_attribute')],
                'ce.attribute_id = ea.attribute_id',
                'ea.attribute_code'
            )
            ->where('ce.attribute_id IN(?)', $attributes)
            ->where('ea.entity_type_id = ?', \Magento\Customer\Api\CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER)
            ->where('ce.entity_id IN (?)', $ids);
    }

    public function getTransactionColumnsAsAttributes()
    {
        return $this->getColumnsByTableName('sales_order_address');
    }

    public function getColumnsByTableName($table)
    {
        $dbConfig = $this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB);
        $select = $this->adapter->select()
            ->from(
                'INFORMATION_SCHEMA.COLUMNS',
                ['COLUMN_NAME', 'name'=>'COLUMN_NAME']
            )
            ->where('TABLE_SCHEMA=?', $dbConfig['connection']['default']['dbname'])
            ->where('TABLE_NAME=?', $this->adapter->getTableName($table));

        $columns =  $this->adapter->fetchPairs($select);
        if (empty($columns))
        {
            throw new NoSuchEntityException("{$table} does not exist.");
        }

        return $columns;
    }

    public function getTableContent($table)
    {
        $select = $this->adapter->select()
            ->from($table, ['*']);

        return $this->adapter->fetchAll($select);
    }

    public function getPriceByType($type, $key)
    {
        $select = $this->getPriceSqlByType($type, $key);
        if(!empty($this->exportIds) && $this->isDelta)
        {
            $select->where('c_p_r.parent_id IN(?)', $this->exportIds);
        }

        return $this->adapter->fetchAll($select);
    }

    public function getPriceSqlByType($type, $key)
    {
        $statusId = $this->getAttributeIdByAttributeCodeAndEntityType('status', \Magento\Catalog\Setup\CategorySetup::CATALOG_PRODUCT_ENTITY_TYPE_ID);
        $select = $this->adapter->select()
            ->from(
                array('c_p_r' => $this->adapter->getTableName('catalog_product_relation')),
                array('parent_id')
            )
            ->join(
                array('t_d' => $this->adapter->getTableName('catalog_product_entity_' . $type)),
                't_d.entity_id = c_p_r.child_id',
                array(
                    'value' => 'MIN(t_d.value)'
                )
            )->join(
                array('t_s' => $this->adapter->getTableName('catalog_product_entity_int')),
                't_s.entity_id = c_p_r.child_id AND t_s.value = 1',
                array()
            )
            ->where('t_d.attribute_id = ?', $key)
            ->where('t_s.attribute_id = ?', $statusId)
            ->group(array('parent_id'));

        return $select;
    }

    /**
     * Get child product attribute value based on the parent product attribute value
     *
     * @param $attributeCode string
     * @param $storeId int
     * @return \Zend_Db_Select
     * @throws \Zend_Db_Select_Exception
     */
    public function getProductAttributeParentUnionSqlByCodeTypeStore($attributeCode, $type, $storeId)
    {
        $attributeId = $this->getAttributeIdByAttributeCodeAndEntityType($attributeCode, \Magento\Catalog\Setup\CategorySetup::CATALOG_PRODUCT_ENTITY_TYPE_ID);
        $select1 = $this->adapter->select()
            ->from(
                ['c_p_e' => $this->adapter->getTableName('catalog_product_entity')],
                ['c_p_e.entity_id']
            )
            ->joinLeft(
                ['c_p_r' => $this->adapter->getTableName('catalog_product_relation')],
                'c_p_e.entity_id = c_p_r.child_id',
                ['parent_id']
            );

        $select1->where('t_d.attribute_id = ?', $attributeId)->where('t_d.store_id = 0 OR t_d.store_id = ?',$storeId);
        if(!empty($this->exportIds) && $this->isDelta) $select1->where('c_p_e.entity_id IN(?)', $this->exportIds);

        $select2 = clone $select1;
        $select2->join(['t_d' => $this->adapter->getTableName('catalog_product_entity_' . $type)],
            't_d.entity_id = c_p_e.entity_id AND c_p_r.parent_id IS NULL',
            [
                't_d.attribute_id',
                't_d.value',
                't_d.store_id'
            ]
        );
        $select1->join(['t_d' => $this->adapter->getTableName('catalog_product_entity_' . $type)],
            't_d.entity_id = c_p_r.parent_id',
            [
                't_d.attribute_id',
                't_d.value',
                't_d.store_id'
            ]
        );

        return $this->adapter->select()->union(
            array($select1, $select2),
            \Zend_Db_Select::SQL_UNION
        );
    }

    /**
     * Query for setting the product status value based on the parent properties and product visibility
     * Fixes the issue when parent product is enabled but child product is disabled.
     *
     * @param $storeId
     * @return mixed
     */
    public function getProductStatusParentDependabilityByStore($storeId)
    {
        $statusId = $this->getAttributeIdByAttributeCodeAndEntityType('status', \Magento\Catalog\Setup\CategorySetup::CATALOG_PRODUCT_ENTITY_TYPE_ID);
        $visibilityId = $this->getAttributeIdByAttributeCodeAndEntityType('visibility', \Magento\Catalog\Setup\CategorySetup::CATALOG_PRODUCT_ENTITY_TYPE_ID);
        
        $parentsCountSql = $this->getProductAttributeParentCountSqlByAttrIdValueStoreId($statusId,  \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED, $storeId);
        $childCountSql = $this->getParentProductAttributeChildCountSqlByAttrIdValueStoreId($statusId,  \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED, $storeId);

        $statusSql = $this->getEavJoinAttributeSQLByStoreAttrIdTable($statusId, $storeId, "catalog_product_entity_int");
        $visibilitySql = $this->getEavJoinAttributeSQLByStoreAttrIdTable($visibilityId, $storeId, "catalog_product_entity_int");
        $select = $this->adapter->select()
            ->from(
                ['c_p_e' => $this->adapter->getTableName('catalog_product_entity')],
                ['c_p_e.entity_id', 'c_p_e.type_id']
            )
            ->joinLeft(
                ['c_p_r' => $this->adapter->getTableName('catalog_product_relation')],
                'c_p_e.entity_id = c_p_r.child_id',
                ['parent_id']
            )
            ->join(
                ['c_p_e_s' => new \Zend_Db_Expr("( ". $statusSql->__toString() . ' )')],
                "c_p_e.entity_id = c_p_e_s.entity_id",
                ['c_p_e_s.attribute_id', 'c_p_e_s.store_id','entity_status'=>'c_p_e_s.value']
            )
            ->join(
                ['c_p_e_v' => new \Zend_Db_Expr("( ". $visibilitySql->__toString() . ' )')],
                "c_p_e.entity_id = c_p_e_v.entity_id",
                ['entity_visibility'=>'c_p_e_v.value']
            );

        if(!empty($this->exportIds) && $this->isDelta) $select->where('c_p_e.entity_id IN(?)', $this->exportIds);
        $configurableType = \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE;
        $groupedType = \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE;
        $visibilityOptions = implode(',', [\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_CATALOG, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH]);
        $finalSelect = $this->adapter->select()
            ->from(
                ["entity_select" => new \Zend_Db_Expr("( ". $select->__toString() . " )")],
                [
                    "entity_select.entity_id",
                    "entity_select.parent_id",
                    "entity_select.store_id",
                    "value" => new \Zend_Db_Expr("
                        (CASE 
                            WHEN (entity_select.type_id = '{$configurableType}' OR entity_select.type_id = '{$groupedType}') AND entity_select.entity_status = '1' THEN IF(child_count.child_count > 0, 1, 2)
                            WHEN entity_select.parent_id IS NULL THEN entity_select.entity_status
                            WHEN entity_select.entity_status = '2' THEN 2 
                            ELSE IF(entity_select.entity_status = '1' AND entity_select.entity_visibility IN ({$visibilityOptions}), 1, IF(entity_select.entity_status = '1' AND parent_count.count > 0, 1, 2))
                         END
                        )"
                    )
                ]
            )
            ->joinLeft(
                ["parent_count"=> new \Zend_Db_Expr("( ". $parentsCountSql->__toString() . " )")],
                "parent_count.entity_id = entity_select.entity_id",
                ["count"]
            )
            ->joinLeft(
                ["child_count"=> new \Zend_Db_Expr("( ". $childCountSql->__toString() . " )")],
                "child_count.entity_id = entity_select.entity_id",
                ["child_count"]
            );

        return $finalSelect;
    }

    /**
     * Default function for accessing product attributes values
     * join them with default store
     * and make a selection on the store id
     *
     * @param $attributeId
     * @param $storeId
     * @param $table
     * @return mixed
     */
    protected function getEavJoinAttributeSQLByStoreAttrIdTable($attributeId, $storeId, $table, $main = 'catalog_product_entity')
    {
        $select = $this->adapter
            ->select()
            ->from(
                array('e' => $main),
                array('entity_id' => 'entity_id')
            );

        $innerCondition = array(
            $this->adapter->quoteInto("{$attributeId}_default.entity_id = e.entity_id", ''),
            $this->adapter->quoteInto("{$attributeId}_default.attribute_id = ?", $attributeId),
            $this->adapter->quoteInto("{$attributeId}_default.store_id = ?", 0)
        );

        $joinLeftConditions = array(
            $this->adapter->quoteInto("{$attributeId}_store.entity_id = e.entity_id", ''),
            $this->adapter->quoteInto("{$attributeId}_store.attribute_id = ?", $attributeId),
            $this->adapter->quoteInto("{$attributeId}_store.store_id IN(?)", $storeId)
        );

        $select
            ->joinInner(
                array($attributeId . '_default' => $table), implode(' AND ', $innerCondition),
                array('default_value' => 'value', 'attribute_id')
            )
            ->joinLeft(
                array("{$attributeId}_store" => $table), implode(' AND ', $joinLeftConditions),
                array("store_value" => 'value', 'store_id')
            );

        $selectSql = $this->adapter->select()
            ->from(
                array('joins' => $select),
                array(
                    'attribute_id'=>'joins.attribute_id',
                    'entity_id' => 'joins.entity_id',
                    'store_id' => new \Zend_Db_Expr("IF (joins.store_value IS NULL OR joins.store_value = '', 0, joins.store_id)"),
                    'value' => new \Zend_Db_Expr("IF (joins.store_value IS NULL OR joins.store_value = '', joins.default_value, joins.store_value)")
                )
            );

        return $selectSql;
    }

    /**
     * Getting count of parent products that have a certain value for an attribute
     * Used for validation of child values
     *
     * @param $attributeId
     * @param $value
     * @param $storeId
     * @return \Magento\Framework\DB\Select
     */
    protected function getProductAttributeParentCountSqlByAttrIdValueStoreId($attributeId, $value, $storeId)
    {
        $storeAttributeValue = $this->getEavJoinAttributeSQLByStoreAttrIdTable($attributeId, $storeId, "catalog_product_entity_int");
        $select = $this->adapter->select()
            ->from(
                ['c_p_r' => $this->adapter->getTableName('catalog_product_relation')],
                ['c_p_r.parent_id']
            )
            ->joinLeft(
                ['c_p_e' => $this->adapter->getTableName('catalog_product_entity')],
                'c_p_e.entity_id = c_p_r.child_id',
                ['c_p_e.entity_id']
            )
            ->join(['t_d' => new \Zend_Db_Expr("( ". $storeAttributeValue->__toString() . ' )')],
                't_d.entity_id = c_p_r.parent_id',
                ['t_d.value']
            );

        $mainSelect = $this->adapter->select()
            ->from(
                ["parent_select"=> new \Zend_Db_Expr("( ". $select->__toString() . ' )')],
                ["count" => new \Zend_Db_Expr("COUNT(parent_select.parent_id)"), 'entity_id']
            )
            ->where("parent_select.value = ?", $value)
            ->group("parent_select.entity_id");

        return $mainSelect;
    }

    /**
     * Getting count of child products that have a certain value for an attribute
     * Used for validation of parent values
     *
     * @param $attributeId
     * @param $value
     * @param $storeId
     * @return \Magento\Framework\DB\Select
     */
    protected function getParentProductAttributeChildCountSqlByAttrIdValueStoreId($attributeId, $value, $storeId)
    {
        $storeAttributeValue = $this->getEavJoinAttributeSQLByStoreAttrIdTable($attributeId, $storeId, "catalog_product_entity_int");
        $select = $this->adapter->select()
            ->from(
                ['c_p_r' => $this->adapter->getTableName('catalog_product_relation')],
                ['c_p_r.child_id']
            )
            ->joinLeft(
                ['c_p_e' => $this->adapter->getTableName('catalog_product_entity')],
                'c_p_e.entity_id = c_p_r.parent_id',
                ['c_p_e.entity_id']
            )
            ->join(['t_d' => new \Zend_Db_Expr("( ". $storeAttributeValue->__toString() . ' )')],
                't_d.entity_id = c_p_r.child_id',
                ['t_d.value']
            )
            ->where('t_d.value = ?', $value);

        $mainSelect = $this->adapter->select()
            ->from(
                ["child_select"=> new \Zend_Db_Expr("( ". $select->__toString() . ' )')],
                ["child_count" => new \Zend_Db_Expr("COUNT(child_select.child_id)"), 'entity_id']
            )
            ->group("child_select.entity_id");

        return $mainSelect;
    }

    public function getProductOptionValuesByStoreAndKey($storeId, $key)
    {
        $select = $this->adapter->select()
            ->from(
                array('a_o' => $this->adapter->getTableName('eav_attribute_option')),
                array(
                    'option_id',
                    new \Zend_Db_Expr("CASE WHEN c_o.value IS NULL THEN b_o.value ELSE c_o.value END as value")
                )
            )->joinLeft(array('b_o' => $this->adapter->getTableName('eav_attribute_option_value')),
                'b_o.option_id = a_o.option_id AND b_o.store_id = 0',
                array()
            )->joinLeft(array('c_o' => $this->adapter->getTableName('eav_attribute_option_value')),
                'c_o.option_id = a_o.option_id AND c_o.store_id = ' . $storeId,
                array()
            )->where('a_o.attribute_id = ?', $key);

        return $this->adapter->fetchAll($select);
    }

    /**
     * We use the crypt key as salt when generating the guest user hash
     * this way we can still optimize on those users behaviour, whitout
     * exposing any personal data. The server salt is there to guarantee
     * that we can't connect guest user profiles across magento installs.
     *
     * @param array $billingColumns
     * @param array $shippingColumns
     * @param $date
     * @param int $mode
     * @return mixed
     */
    public function prepareTransactionsSelectByShippingBillingModeSql($account, $billingColumns =[], $shippingColumns = [], $mode = 1)
    {
        $salt = $this->adapter->quote(
            ((string) $this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_CRYPT_KEY)) .
            $account
        );
        $sales_order_table = $this->adapter->getTableName('sales_order');
        $sales_order_item = $this->adapter->getTableName('sales_order_item');
        $sales_order_address =  $this->adapter->getTableName('sales_order_address');
        $sales_order_payment =  $this->adapter->getTableName('sales_order_payment');

        $select = $this->adapter
            ->select()
            ->from(
                array('order' => $sales_order_table),
                array(
                    'entity_id',
                    'status',
                    'updated_at',
                    'created_at',
                    'customer_id',
                    'base_subtotal',
                    'shipping_amount',
                    'shipping_method',
                    'customer_is_guest',
                    'customer_email',
                    'order_currency_code'
                )
            )
            ->joinLeft(
                array('item' => $sales_order_item),
                'order.entity_id = item.order_id',
                array(
                    'product_id',
                    'product_options',
                    'price',
                    'original_price',
                    'product_type',
                    'qty_ordered',
                )
            )
            ->joinLeft(
                array('guest' => $sales_order_address),
                'order.billing_address_id = guest.entity_id',
                array(
                    'guest_id' => 'IF(guest.email IS NOT NULL, SHA1(CONCAT(guest.email, ' . $salt . ')), NULL)'
                )
            )
            ->joinLeft(
                array('payment' => $sales_order_payment),
                'order.entity_id = payment.entity_id',
                array(
                    'payment_method' => 'method',
                    'payment_title' => 'additional_information'
                )
            );

        if (!$mode) {
            $select->where('DATE(order.created_at) >=  DATE(NOW() - INTERVAL 1 MONTH)');
        }

        if(!empty($billingColumns) && !empty($shippingColumns))
        {
            $select
                ->joinLeft(
                    array('billing_address' => $sales_order_address),
                    'order.billing_address_id = billing_address.entity_id',
                    $billingColumns
                )
                ->joinLeft(
                    array('shipping_address' => $sales_order_address),
                    'order.shipping_address_id = shipping_address.entity_id',
                    $shippingColumns
                );
        }

        return $select;
    }

    public function getTransactionsByLimitPage($limit, $page, $initialSelect)
    {
        $select = $this->adapter->select()
            ->from(['transactions_export' => new \Zend_Db_Expr("( " . $initialSelect->__toString() . ')')], ['*'])
            ->limit($limit, ($page - 1) * $limit);

        return $this->adapter->fetchAll($select);
    }

    public function getProductStockInformation()
    {
        $select = $this->adapter->select()
            ->from(
                $this->adapter->getTableName('cataloginventory_stock_status'),
                ['entity_id' => 'product_id', 'stock_status', 'qty']
            )
            ->where('stock_id = ?', 1);
        if(!empty($this->exportIds) && $this->isDelta)
        {
            $select->where('product_id IN(?)', $this->exportIds);
        }

        return $this->adapter->fetchAll($select);
    }

    public function getProductWebsiteInformation()
    {
        $select = $this->adapter->select()
            ->from(
                ['c_p_w' => $this->adapter->getTableName('catalog_product_website')],
                ['entity_id' => 'product_id', 'website_id']
            )->joinLeft(
                ['s_w' => $this->adapter->getTableName('store_website')],
                's_w.website_id = c_p_w.website_id',
                ['s_w.name']
            );
        if(!empty($this->exportIds) && $this->isDelta)
        {
            $select->where('product_id IN(?)', $this->exportIds);
        }

        return $this->adapter->fetchAll($select);
    }

    public function getProductSuperLinkInformation()
    {
        $select = $this->adapter->select()
            ->from(
                $this->adapter->getTableName('catalog_product_super_link'),
                ['entity_id' => 'product_id', 'parent_id', 'link_id']
            );
        if(!empty($this->exportIds) && $this->isDelta)
        {
            $select->where('product_id IN(?)', $this->exportIds);
        }

        return $this->adapter->fetchAll($select);
    }

    public function getProductLinksInformation()
    {
        $select = $this->adapter->select()
            ->from(
                ['pl'=> $this->adapter->getTableName('catalog_product_link')],
                ['entity_id' => 'product_id', 'linked_product_id', 'lt.code']
            )
            ->joinLeft(
                ['lt' => $this->adapter->getTableName('catalog_product_link_type')],
                'pl.link_type_id = lt.link_type_id', []
            )
            ->where('lt.link_type_id = pl.link_type_id');
        if(!empty($this->exportIds) && $this->isDelta)
        {
            $select->where('product_id IN(?)', $this->exportIds);
        }

        return $this->adapter->fetchAll($select);
    }

    protected function getProductParentCategoriesInformationSql()
    {
        $select = $this->adapter->select()
            ->from(
                ['c_p_e' => $this->adapter->getTableName('catalog_product_entity')],
                ['c_p_e.entity_id']
            )
            ->joinLeft(
                ['c_p_r' => $this->adapter->getTableName('catalog_product_relation')],
                'c_p_e.entity_id = c_p_r.child_id',
                []
            );
        if(!empty($this->exportIds) && $this->isDelta)
        {
            $select->where('product_id IN(?)', $this->exportIds);
        }

        return $select;
    }

    public function getProductParentCategoriesInformation()
    {
        $selectTwo = $this->getProductParentCategoriesInformationSql();
        $selectOne = clone $selectTwo;
        $selectOne->join(
            ['c_c_p' => $this->adapter->getTableName('catalog_category_product')],
            'c_c_p.product_id = c_p_r.parent_id',
            ['category_id']
        );
        $selectTwo->join(
            ['c_c_p' => $this->adapter->getTableName('catalog_category_product')],
            'c_c_p.product_id = c_p_e.entity_id AND c_p_r.parent_id IS NULL',
            ['category_id']
        );

        $select = $this->adapter->select()
            ->union(
                [$selectOne, $selectTwo],
                \Magento\Framework\DB\Select::SQL_UNION_ALL
            );

        return $this->adapter->fetchAll($select);
    }

    public function getProductParentCategoriesInformationByDuplicateIds($duplicateIds = [])
    {
        $select = $this->adapter->select()
            ->from(
                ['c_p_e' => $this->adapter->getTableName('catalog_product_entity')],
                ['entity_id']
            )->join(
                ['c_c_p' => $this->adapter->getTableName('catalog_category_product')],
                'c_c_p.product_id = c_p_e.entity_id',
                ['category_id']
            )->where('c_p_e.entity_id IN(?)', $duplicateIds);

        return $this->adapter->fetchAll($select);
    }

    protected function getProductParentTitleInformationSql($storeId)
    {
        $select = $this->adapter->select()
            ->from(
                ['c_p_e' => $this->adapter->getTableName('catalog_product_entity')],
                ['c_p_e.entity_id']
            )
            ->joinLeft(
                ['c_p_r' => $this->adapter->getTableName('catalog_product_relation')],
                'c_p_e.entity_id = c_p_r.child_id',
                ['c_p_r.parent_id']
            );
        $select->where('t_d.attribute_id = ?', $attrId)->where('t_d.store_id = 0 OR t_d.store_id = ?',$storeId);
        if(!empty($this->exportIds) && $this->isDelta)
        {
            $select->where('c_p_e.entity_id IN(?)', $this->exportIds);
        }

        return $select;
    }

    public function getProductParentTitleInformationByStore($storeId)
    {
        $selectTwo = $this->getProductParentTitleInformationSql($storeId);
        $selectOne = clone $selectTwo;
        $selectOne->join(
            ['t_d' => $this->adapter->getTableName('catalog_product_entity_varchar')],
            't_d.entity_id = c_p_e.entity_id AND c_p_r.parent_id IS NULL',
            ['t_d.value', 't_d.store_id']
        );
        $selectTwo->join(
            ['t_d' => $this->adapter->getTableName('catalog_product_entity_varchar')],
            't_d.entity_id = c_p_r.parent_id',
            ['t_d.value', 't_d.store_id']
        );

        $select = $this->adapter->select()
            ->union(
                [$selectOne, $selectTwo],
                \Magento\Framework\DB\Select::SQL_UNION_ALL
            );

        return $this->adapter->fetchAll($select);
    }

    public function getProductParentTitleInformationByStoreAttrDuplicateIds($storeId, $attrId, $duplicateIds = [])
    {
        $select = $this->adapter->select()
            ->from(
                ['c_p_e' => $this->adapter->getTableName('catalog_product_entity')],
                ['entity_id', new \Zend_Db_Expr("CASE WHEN c_p_e_v_b.value IS NULL THEN c_p_e_v_a.value ELSE c_p_e_v_b.value END as value")]
            )->joinLeft(
                ['c_p_e_v_a' => $this->adapter->getTableName('catalog_product_entity_varchar')],
                '(c_p_e_v_a.attribute_id = ' . $attrId . ' AND c_p_e_v_a.store_id = 0) AND (c_p_e_v_a.entity_id = c_p_e.entity_id)',
                []
            )->joinLeft(
                ['c_p_e_v_b' => $this->adapter->getTableName('catalog_product_entity_varchar')],
                '(c_p_e_v_b.attribute_id = ' . $attrId . ' AND c_p_e_v_b.store_id = ' . $storeId . ') AND (c_p_e_v_b.entity_id = c_p_e.entity_id)',
                []
            )->where('c_p_e.entity_id IN (?)', $duplicateIds);

        return $this->adapter->fetchAll($select);
    }

    public function setExportIds($exportIds = [])
    {
        $this->exportIds = $exportIds;
        return $this;
    }

    public function getExportIds()
    {
        return $this->exportIds;
    }

    public function isDelta($isDelta)
    {
        $this->isDelta = $isDelta;
        return $this;
    }

}