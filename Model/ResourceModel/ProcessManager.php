<?php
namespace Boxalino\Intelligence\Model\ResourceModel;

use \Magento\Framework\App\ResourceConnection;

/**
 * Keeps most of db access for the exporter class
 *
 * Class Exporter
 * @package Boxalino\Intelligence\Model\ResourceModel
 */
class ProcessManager
{

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $adapter;
    
    /**
     * BxIndexer constructor.
     * @param LoggerInterface $logger
     * @param ResourceConnection $resource
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     */
    public function __construct(
        ResourceConnection $resource
    ) {
        $this->adapter = $resource->getConnection();
    }
    
    /**
     * Check product IDs from last delta run
     *
     * @param null | string $date
     * @return array
     */
    public function getProductIdsByUpdatedAt($date)
    {
        $select = $this->adapter->select()
            ->from(
                ['c_p_e' => $this->adapter->getTableName('catalog_product_entity')],
                ['entity_id']
            )->where("DATE_FORMAT(c_p_e.updated_at, '%Y-%m-%d %H:%i:%s') >=  DATE_FORMAT(?, '%Y-%m-%d %H:%i:%s')", $date);

        return $this->adapter->fetchCol($select);
    }

    /**
     * Check wether delta must be run due to existing updates in categories
     *
     * @param null $date
     * @return bool
     */
    public function hasDeltaReadyCategories($date)
    {
        $select = $this->adapter->select()
            ->from(
                ['c_c_e'=> $this->adapter->getTableName("catalog_category_entity")],
                ['entity_id']
            )->where("DATE_FORMAT(c_c_e.updated_at, '%Y-%m-%d %H:%i:%s') >=  DATE_FORMAT(?, '%Y-%m-%d %H:%i:%s')", $date);

        return (bool) $this->adapter->query($select)->rowCount();
    }

    /**
     *  Get the latest updated product IDs to be used for delta export as a mock, in case there are category updates
     * @return array
     */
    public function getLatestUpdatedProductIds()
    {
        $select = $this->adapter->select()
            ->from(
                ['c_p_e' => $this->adapter->getTableName('catalog_product_entity')],
                [new \Zend_Db_Expr('MAX(entity_id)')]
            )->order("c_p_e.updated_at DESC")
            ->group(['c_p_e.attribute_set_id', 'c_p_e.type_id', 'c_p_e.has_options', 'c_p_e.required_options']);

        return $this->adapter->fetchCol($select);
    }

    /**
     * Get the products belonging to the category to update them as well
     * @param $date
     * @return array
     */
    public function getProductsOnUpdatedCategories($date)
    {
        $select = $this->adapter->select()
            ->from(
                ['c_c_e'=> $this->adapter->getTableName("catalog_category_entity")],
                []
            )->joinLeft(
                ['c_p_r' => $this->adapter->getTableName('catalog_category_product')],
                "c_c_e.entity_id = c_p_r.category_id",
                ['product_id']
            )->where("DATE_FORMAT(c_c_e.updated_at, '%Y-%m-%d %H:%i:%s') >=  DATE_FORMAT(?, '%Y-%m-%d %H:%i:%s')", $date);

        return $this->adapter->fetchCol($select);
    }

    /**
     * Rollback indexer latest updated date in case of error
     *
     * @param $id
     * @param $updated
     * @return int
     */
    public function updateIndexerUpdatedAt($id, $updated)
    {
        $dataBind = [
            "updated"=>$updated,
            "indexer_id"=>$id
        ];

        return $this->adapter->insertOnDuplicate(
            $this->adapter->getTableName("boxalino_export"),
            $dataBind, ["updated"]
        );
    }

    /**
     * @param $id
     * @return string
     */
    public function getLatestUpdatedAtByIndexerId($id)
    {
        $select = $this->adapter->select()
            ->from($this->adapter->getTableName("boxalino_export"), ["updated"])
            ->where("indexer_id = ?", $id);

        return $this->adapter->fetchOne($select);
    }

}