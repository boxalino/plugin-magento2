<?php
namespace Boxalino\Frontend\Model\Resource\Advanced;
use Magento\CatalogSearch\Model\ResourceModel\Advanced\Collection as AdvancedCollection;

class Collection extends AdvancedCollection
{
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Catalog\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Catalog\Model\Indexer\Product\Flat\State $catalogProductFlatState,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory,
        \Magento\Catalog\Model\ResourceModel\Url $catalogUrl,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Customer\Api\GroupManagementInterface $groupManagement,
        \Magento\CatalogSearch\Model\Advanced\Request\Builder $requestBuilder,
        \Magento\Search\Model\SearchEngine $searchEngine,

        \Magento\Framework\Search\Adapter\Mysql\TemporaryStorageFactory $temporaryStorageFactory,
        $connection = null)
    {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager,
            $eavConfig, $resource, $eavEntityFactory, $resourceHelper, $universalFactory,
            $storeManager, $moduleManager, $catalogProductFlatState, $scopeConfig,
            $productOptionFactory, $catalogUrl, $localeDate, $customerSession, $dateTime,
            $groupManagement, $requestBuilder, $searchEngine, $temporaryStorageFactory, $connection);
    }

    /**
     * Add products id to search
     *
     * @param array $ids
     * @return Mage_CatalogSearch_Model_Resource_Advanced_Collection
     */
    public function addIdFromBoxalino($ids)
    {
        $this->addFieldToFilter('entity_id', array('in' => $ids));
        return $this;
    }

}
