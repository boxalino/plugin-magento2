<?php
namespace Boxalino\Intelligence\Model\Indexer;

use Boxalino\Intelligence\Helper\BxindexConfig;
use Boxalino\Intelligence\Helper\BxFiles;
use Boxalino\Intelligence\Helper\BxGeneral;

use Magento\Indexer\Model\Indexer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Catalog\Model\ProductFactory;

use \Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
class BxExporter implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface{

	/**
	 *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
	
	/**
     * @var \Psr\Log\LoggerInterface
     */
	protected $logger;
	
	/**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;
	
	/**
     * @var \Magento\Catalog\Helper\Product\Flat\Indexer
     */
    protected $productHelper;
	
	/**
     * @var \Boxalino\Intelligence\Helper\BxGeneral
     */
    protected $bxGeneral;
	
	/**
     * @var \Magento\Catalog\Model\ResourceModel\Category\Collection
     */
    protected $categoryCollection;
	
	/**
     * @var \Magento\Framework\App\DeploymentConfig
     */
    protected $deploymentConfig;
	
	/**
     * @var \Magento\Catalog\Model\ProductFactory;
     */
    protected $productFactory;
	
	/**
     * @var \Magento\Catalog\Helper\Image;
     */
    protected $_imageHelper;
	
	/**
     * @var \Magento\Catalog\Block\Product\Context;
     */
    protected $context;
	
	/**
     * @var \Magento\Framework\App\ResourceConnection
     */
	protected $rs;
	
	/**
	* @var \Magento\Framework\App\ProductMetadata
	*/
	protected $productMetaData;
	
	/**
	* @var \Magento\Catalog\Model\Product\Type\Price
	*/
	private $typePrice;
	
	/**
	* the list of ids to update, do a full export in case the delta is array is empty
	*/
	protected $deltaIds = array();
	
	/**
	* @var \Boxalino\Intelligence\Helper\BxIndexConfig : containing the access to the configuration of each store to export
	*/
	private $config = null;
	
	/**
	* Cache of entity id types from table eav_entity_type
	* Only used in function: getEntityIdFor
	*/
	protected $_entityIds = null;

	/**
	* Cache of product images
	*/
    protected $_productsImages = array();
	
	/**
	* Cache of product image thumnails
	*/
    protected $_productsThumbnails = array();

	/**
	 * Cache of product default Urls
	 */
	protected $_productsDefaultUrls = array();

	/**
	 * Indexer model, responsible for loading our $boxalinoIndexer
	 */
	protected $indexer;

	/**
	 * Boxalino indexer class
	 */
	protected $boxalinoIndexer = null;


	private $bxData = null;

	protected $linkedProductAttributes;

    public function __construct(
        StoreManagerInterface $storeManager,
		LoggerInterface $logger,
		Filesystem $filesystem,
		\Magento\Catalog\Helper\Product\Flat\Indexer $productHelper,
		\Magento\Catalog\Model\ResourceModel\Category\Collection $categoryCollection,
		\Magento\Framework\App\ResourceConnection $rs,
		\Magento\Framework\App\DeploymentConfig $deploymentConfig,
		ProductFactory $productFactory,
		\Magento\Catalog\Block\Product\Context $context,
		\Magento\Framework\App\ProductMetadata $productMetaData,
		\Magento\Catalog\Model\Product\Type\Price $typePrice,
		\Magento\Indexer\Model\Indexer $indexer
    )
    {
	   $this->indexer = $indexer;
       $this->storeManager = $storeManager;
	   $this->logger = $logger;
	   $this->filesystem = $filesystem;
	   $this->productHelper = $productHelper;
	   $this->categoryCollection = $categoryCollection;
	   $this->rs = $rs;
	   $this->deploymentConfig = $deploymentConfig;
	   $this->productFactory = $productFactory;
	   $this->context = $context;
	   $this->_imageHelper = $context->getImageHelper();
	   $this->productMetaData = $productMetaData;
	   $this->typePrice = $typePrice;
	   
	   $this->bxGeneral = new BxGeneral();

	   $libPath = __DIR__ . '/../../Lib';
		require_once($libPath . '/BxClient.php');
		\com\boxalino\bxclient\v1\BxClient::LOAD_CLASSES($libPath);
    }

    public function executeRow($id){
		$this->exportStores(array($id));
    }

    public function executeList(array $ids){
		$this->exportStores($ids);
    }

    public function execute($ids){
		$this->exportStores($ids);
    }
	
    public function executeFull(){
		$this->boxalinoIndexer = $this->indexer->load('boxalino_indexer');
		if($this->boxalinoIndexer->isScheduled()){
			//enable delta export
			$this->boxalinoIndexer->setScheduled(true);
		};
		$this->exportStores($this->checkForDeltaIds());
	}

	protected function exportStores($deltaIds=array()) {
		$this->logger->info("bxLog: starting exportStores");

		$this->deltaIds = $deltaIds;
		
		$this->config = new BxIndexConfig($this->storeManager->getWebsites());
		$this->logger->info("bxLog: retrieved index config: " . $this->config->toString());
		
		foreach ($this->config->getAccounts() as $account) {
			
			$this->logger->info("bxLog: initialize files on account: " . $account);
            $files = new BxFiles($this->filesystem, $this->logger, $account, $this->config);
			
			$bxClient = new \com\boxalino\bxclient\v1\BxClient($account, $this->config->getAccountPassword($account), "");
			$this->bxData = new \com\boxalino\bxclient\v1\BxData($bxClient, $this->config->getAccountLanguages($account), $this->config->isAccountDev($account), $this->getIndexType() == 'delta');
			
			$this->logger->info("bxLog: verify credentials for account: " . $account);
			$this->bxData->verifyCredentials();
			
			$this->logger->info('bxLog: Preparing the attributes and category data for each language of the account: ' . $account);
			$categories = array();
			$attributes = null;
			foreach ($this->config->getAccountLanguages($account) as $language) {
				
				$store = $this->config->getStore($account, $language);
				
				$this->logger->info('bxLog: Start getStoreProductAttributes for language . ' . $language . ' on store:' . $store->getId());
				$attributes = $this->getStoreProductAttributes($account, $store);
				$this->logger->info('bxLog: Start exportCategories for language . ' . $language . ' on store:' . $store->getId());
				$categories = $this->exportCategories($store, $language, $categories);
			}

			$this->logger->info('bxLog: Export the customers, transactions and product files for account: ' . $account);
			$this->exportCustomers($account, $files);

			$this->exportTransactions($account, $files);

			$this->exportProducts($account, $files, $attributes);
			$this->logger->info('bxLog: Prepare the final files: ' . $account);

			$this->logger->info('bxLog: Prepare XML configuration file: ' . $account);
			$this->prepareData($account, $files, $categories);

			if($this->getIndexType() != 'delta') {
				try {
					$this->logger->info('bxLog: Push the XML configuration file to the Data Indexing server for account: ' . $account);
					$this->bxData->pushDataSpecifications();
				} catch(\Exception $e) {
					$this->boxalinoIndexer->setReset(true);
					$value = @json_decode($e->getMessage(), true);

					if(isset($value['error_type_number']) && $value['error_type_number'] == 3) {
						$this->logger->info('bxLog: Try to push the XML file a second time, error 3 happens always at the very first time but not after: ' . $account);
						$this->bxData->pushDataSpecifications();
					} else {
						throw $e;
					}
				}

				$this->logger->info('bxLog: Publish the configuration chagnes from the magento2 owner for account: ' . $account);
				$publish = $this->config->publishConfigurationChanges($account);

				$changes = $this->bxData->publishChanges($publish);
				if(sizeof($changes['changes']) > 0 && !$publish) {
					$this->logger->warn("changes in configuration detected butnot published as publish configuration automatically option has not been activated for account: " . $account);
				}
			}

			$this->logger->info('bxLog: Push the Zip data file to the Data Indexing server for account: ' . $account);
			try{
				$this->bxData->pushData();
			}catch(\Exception $e){
				$this->boxalinoIndexer->setReset(true);
				throw $e;
			}

            $this->logger->info('bxLog: Finished account: ' . $account);
        }
		if($this->getIndexType() == 'delta'){
			$this->clearDeltaIds();
		}
		$this->logger->info("bxLog: finished exportStores");
	}

	protected function prepareData($account, $files, $categories, $tags = null, $productTags = null) {
		$withTag = ($tags != null && $productTags != null) ? true : false;
		$languages = $this->config->getAccountLanguages($account);
		$categories = array_merge(array(array_keys(end($categories))), $categories);
		$files->savePartToCsv('categories.csv', $categories);

		$labelColumns = array();
		foreach ($languages as $lang) {
			$labelColumns[$lang] = 'value_' . $lang;
		}
		$this->bxData->addCategoryFile($files->getPath('categories.csv'), 'category_id', 'parent_id', $labelColumns);
		$productToCategoriesSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_categories.csv'), 'entity_id');
		$this->bxData->setCategoryField($productToCategoriesSourceKey, 'category_id');

	}

    /**
     * @description Preparing categories to export
     * @return array Categories
     */
    protected function exportCategories($store, $language, $transformedCategories)
    {
		$db = $this->rs->getConnection();
		$select = $db->select()
			->from(
				array('c_t' => $this->rs->getTableName('catalog_category_entity')),
				array('entity_id', 'parent_id')
			)
			->joinInner(
				array('c_v' => $this->rs->getTableName('catalog_category_entity_varchar')),
				'c_v.entity_id = c_t.entity_id',
				array('c_v.value', 'c_v.store_id')
			)->where('c_v.attribute_id = ?', 42)->where('c_v.store_id = ? OR c_v.store_id = 0', $store->getId());

		$result = $db->fetchAll($select);
		foreach($result as $r){
			if (!$r['parent_id'])  {
				continue;
			}
			if(isset($transformedCategories[$r['entity_id']])) {
				$transformedCategories[$r['entity_id']]['value_' .$language] = $r['value'];
				continue;
			}
			$transformedCategories[$r['entity_id']] = array('category_id' => $r['entity_id'], 'parent_id' => $r['parent_id'], 'value_' . $language => $r['value']);
		}
		return $transformedCategories;
    }

    /**
     * @description Merge default attributes with attributes added by user
     * @return void
     */
    protected function getStoreProductAttributes($account, $store)
    {
		
		$this->logger->info('bxLog: get all product attributes for store: ' . $store->getId());
		$attributes = array();
		foreach ($this->productHelper->getAttributes() as $attribute) {
			if ($attribute->getAttributeCode() != null && strlen($attribute->getAttributeCode()) > 0) {
				$attributes[$attribute->getId()] = $attribute->getAttributeCode();
			}
		}
		
		$requiredProperties = array(
            'entity_id',
            'name',
            'description',
            'short_description',
            'sku',
            'price',
            'special_price',
            'special_from_date',
            'special_to_date',
            'category_ids',
            'visibility',
            'status'
        );

		$this->logger->info('bxLog: get configured product attributes for store: ' . $store->getId());
		$filteredAttributes = $this->config->getAccountProductsProperties($account, $attributes, $requiredProperties);
		
		foreach($attributes as $k => $attribute) {
			if(!in_array($attribute, $filteredAttributes)) {
				unset($attributes[$k]);
			}
		}
		
		$this->logger->info('bxLog: returning configured product attributes for store ' . $store->getId() . ': ' . implode(',', array_values($attributes)));
		return $attributes;
    }

    /**
     * @description Preparing customers to export
     * @param Mage_Core_Model_Website $website
     * @return void
     *
     */
    protected function exportCustomers($account, $files)
    {
		if(!$this->config->isCustomersExportEnabled($account) || $this->getIndexType() == 'delta') {
			return;
		}
		
		$this->logger->info('bxLog: starting exporting customers for account: ' . $account);

        $limit = 1000;
        $count = $limit;
        $page = 1;
        $header = true;

        $attrsFromDb = array(
            'int' => array(),
            'static' => array(), // only supports email
            'varchar' => array(),
            'datetime' => array(),
        );
		
		$this->logger->info('bxLog: get final customer attributes for account: ' . $account);
        $customer_attributes = $this->getCustomerAttributes($account);
		
		$this->logger->info('bxLog: get customer attributes backend types for account: ' . $account);
		$db = $this->rs->getConnection();
		$select = $db->select()
            ->from(
                array('main_table' => $this->rs->getTableName('eav_attribute')),
                array(
                    'aid' => 'attribute_id',
                    'backend_type',
                )
            )
            ->joinInner(
                array('additional_table' => $this->rs->getTableName('customer_eav_attribute')),
                'additional_table.attribute_id = main_table.attribute_id',
                array()
            )
            ->where('main_table.entity_type_id = ?', $this->getEntityIdFor('customer'))
            ->where('main_table.attribute_code IN (?)', $customer_attributes);

        foreach ($db->fetchAll($select) as $attr) {
            if (isset($attrsFromDb[$attr['backend_type']])) {
                $attrsFromDb[$attr['backend_type']][] = $attr['aid'];
            }
        }

        do {
            $this->logger->info('bxLog: Customers - load page $page for account: ' . $account);
			$customers_to_save = array();

            $customers = array();

            $this->logger->info('bxLog: Customers - get customer ids for page $page for account: ' . $account);
			$select = $db->select()
                ->from(
                    $this->rs->getTableName('customer_entity'),
                    array('entity_id', 'created_at', 'updated_at')
                )
                ->limit($limit, ($page - 1) * $limit);
			foreach ($db->fetchAll($select) as $r) {
                $customers[$r['entity_id']] = array('id' => $r['entity_id']);
            }

            $this->logger->info('bxLog: Customers - prepare side queries page $page for account: ' . $account);
			$ids = array_keys($customers);
            $columns = array(
                'entity_id',
                'attribute_id',
                'value',
            );

            $select = $db->select()
                ->where('ce.entity_type_id = ?', 1)
                ->where('ce.entity_id IN (?)', $ids);

            $select1 = null;
            $select2 = null;
            $select3 = null;
            $select4 = null;

            $selects = array();

            if (count($attrsFromDb['varchar']) > 0) {
                $select1 = clone $select;
                $select1->from(array('ce' => $this->rs->getTableName('customer_entity_varchar')), $columns)
                    ->joinLeft(array('ea' => $this->rs->getTableName('eav_attribute')), 'ce.attribute_id = ea.attribute_id', 'ea.attribute_code')
                    ->where('ce.attribute_id IN(?)', $attrsFromDb['varchar']);
                $selects[] = $select1;
            }

            if (count($attrsFromDb['int']) > 0) {
                $select2 = clone $select;
                $select2->from(array('ce' => $this->rs->getTableName('customer_entity_int')), $columns)
                    ->joinLeft(array('ea' => $this->rs->getTableName('eav_attribute')), 'ce.attribute_id = ea.attribute_id', 'ea.attribute_code')
                    ->where('ce.attribute_id IN(?)', $attrsFromDb['int']);
                $selects[] = $select2;
            }

            if (count($attrsFromDb['datetime']) > 0) {
                $select3 = clone $select;
                $select3->from(array('ce' => $this->rs->getTableName('customer_entity_datetime')), $columns)
                    ->joinLeft(array('ea' => $this->rs->getTableName('eav_attribute')), 'ce.attribute_id = ea.attribute_id', 'ea.attribute_code')
                    ->where('ce.attribute_id IN(?)', $attrsFromDb['datetime']);
                $selects[] = $select3;
            }

            // only supports email
            if (count($attrsFromDb['static']) > 0) {
                $attributeId = current($attrsFromDb['static']);
                $select4 = $db->select()
                    ->from(array('ce' => $this->rs->getTableName('customer_entity')), array(
                        'entity_id' => 'entity_id',
                        'attribute_id' =>  new \Zend_Db_Expr($attributeId),
                        'value' => 'email',
                    ))
                    ->joinLeft(array('ea' => $this->rs->getTableName('eav_attribute')), 'ea.attribute_id = ' . $attributeId, 'ea.attribute_code')
					->where('ce.entity_id IN (?)', $ids);
                $selects[] = $select4;
            }

            $select = $db->select()
                ->union(
                    $selects,
                    \Magento\Framework\DB\Select::SQL_UNION_ALL
                );

            $this->logger->info('bxLog: Customers - retrieve data for side queries page $page for account: ' . $account);
			foreach ($db->fetchAll($select) as $r) {
                $customers[$r['entity_id']][$r['attribute_code']] = $r['value'];
            }

            $select = null;
            $select1 = null;
            $select2 = null;
            $select3 = null;
            $select4 = null;
            $selects = null;

            $this->logger->info('bxLog: Customers - get postcode for page $page for account: ' . $account);
			$select = $db->select()
                ->from(
                    $this->rs->getTableName('eav_attribute'),
                    array(
                        'attribute_id',
                        'attribute_code',
                    )
                )
                ->where('entity_type_id = ?', $this->getEntityIdFor('customer_address'))
                ->where('attribute_code IN (?)', array('country_id', 'postcode'));

            $addressAttr = array();
            foreach ($db->fetchAll($select) as $r) {
                $addressAttr[$r['attribute_id']] = $r['attribute_code'];
            }
            $addressIds = array_keys($addressAttr);

            $this->logger->info('bxLog: Customers - load data per customer for page $page for account: ' . $account);
			foreach ($customers as $customer) {
                $id = $customer['id'];

                $select = $db->select()
                    ->from(
                        $this->rs->getTableName('customer_address_entity'),
                        array('entity_id')
                    )
                    ->where('parent_id = ?', $id)
                    ->order('entity_id DESC')
                    ->limit(1);

                $select = $db->select()
                    ->from(
                        $this->rs->getTableName('customer_address_entity_varchar'),
                        array('attribute_id', 'value')
                    )
                    ->where('entity_id = ?', $select)
                    ->where('attribute_id IN(?)', $addressIds);

                $billingResult = array();
                foreach ($db->fetchAll($select) as $br) {
                    if (in_array($br['attribute_id'], $addressIds)) {
                        $billingResult[$addressAttr[$br['attribute_id']]] = $br['value'];
                    }
                }

                $countryCode = null;
                if (isset($billingResult['country_id'])) {
                    $countryCode = $billingResult['country_id'];
                }

                if (array_key_exists('gender', $customer)) {
                    if ($customer['gender'] % 2 == 0) {
                        $customer['gender'] = 'female';
                    } else {
                        $customer['gender'] = 'male';
                    }
                }

                $customer_to_save = array(
                    'customer_id' => $id,
                    'country' => !empty($countryCode) ? $this->_helperExporter->getCountry($countryCode)->getName() : '',
                    'zip' => array_key_exists('postcode', $billingResult) ? $billingResult['postcode'] : '',
                );
                foreach($customer_attributes as $attr) {
                    $customer_to_save[$attr] = array_key_exists($attr, $customer) ? $customer[$attr] : '';
                }
                $customers_to_save[] = $customer_to_save;
            }

            $data = $customers_to_save;

            if (count($customers) == 0 && $header) {
                return null;
            }

            if ($header) {
                $data = array_merge(array(array_keys(end($customers_to_save))), $customers_to_save);
                $header = false;
            }
            $this->logger->info('bxLog: Customers - save to file for page $page for account: ' . $account);
			$files->savePartToCsv('customers.csv', $data);
            $data = null;

            $count = count($customers_to_save);
            $page++;

        } while ($count >= $limit);
        $customers = null;

		if ($this->config->isCustomersExportEnabled($account)) {

			$customerSourceKey = $this->bxData->addMainCSVCustomerFile($files->getPath('customers.csv'), 'customer_id');

			foreach (
				$customer_attributes as $prop
			) {
				if($prop == 'id') {
					continue;
				}

				$this->bxData->addSourceStringField($customerSourceKey, $prop, $prop);
			}
		}
        $this->logger->info('bxLog: Customers - end of exporting for account: ' . $account);
    }

    /**
     * Fetch entity id for a entity type.
     *
     * @param string $entityType
     * @return null|string
     */
    public function getEntityIdFor($entityType)
    {
        if ($this->_entityIds == null) {
            $db = $this->rs->getConnection();
            $select = $db->select()
                ->from(
                    $this->rs->getTableName('eav_entity_type'),
                    array('entity_type_id', 'entity_type_code')
                );
            $this->_entityIds = array();
            foreach ($db->fetchAll($select) as $row) {
                $this->_entityIds[$row['entity_type_code']] = $row['entity_type_id'];
            }
        }
        return array_key_exists($entityType, $this->_entityIds) ? $this->_entityIds[$entityType] : null;
    }
	
	protected function getTransactionAttributes($account) {
		$this->logger->info('bxLog: get all transaction attributes for account: ' . $account);
		$dbConfig = $this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB);
		if(!isset($dbConfig['connection']['default']['dbname'])) {
			$this->logger->warn("ConfigOptionsListConstants::CONFIG_PATH_DB doesn't provide a dbname in ['connection']['default']['dbname']");
			return array();
		}
		$attributes = array();
		$db = $this->rs->getConnection();
		$select = $db->select()
			->from(
				'INFORMATION_SCHEMA.COLUMNS',
				array('COLUMN_NAME')
			)
			->where('TABLE_SCHEMA=?', $dbConfig['connection']['default']['dbname'])
			->where('TABLE_NAME=?', $this->rs->getTableName('sales_order_address'));
		$this->_entityIds = array();
		foreach ($db->fetchAll($select) as $row) {
			$attributes[$row['COLUMN_NAME']] = $row['COLUMN_NAME'];
		}
		
		$requiredProperties = array();
		
		$this->logger->info('bxLog: get configured transaction attributes for account: ' . $account);
		$filteredAttributes = $this->config->getAccountTransactionsProperties($account, $attributes, $requiredProperties);
		
		foreach($attributes as $k => $attribute) {
			if(!in_array($attribute, $filteredAttributes)) {
				unset($attributes[$k]);
			}
		}
		$this->logger->info('bxLog: returning configured transaction attributes for account ' . $account . ': ' . implode(',', array_values($attributes)));
		
		return $attributes;
	}

    /**
     * @description Merge default customer attributes with customer attributes added by user
     * @param array $attributes optional, array to merge the user defined attributes into
     * @return array
     */
    protected function getCustomerAttributes($account)
    {
		$attributes = array();
		
		$this->logger->info('bxLog: get all customer attributes for account: ' . $account);
		$db = $this->rs->getConnection();
		$select = $db->select()
            ->from(
                array('main_table' => $this->rs->getTableName('eav_attribute')),
                array(
                    'attribute_code',
                )
            )
            ->where('main_table.entity_type_id = ?', $this->getEntityIdFor('customer'));
			
		foreach ($db->fetchAll($select) as $attr) {
            $attributes[$attr['attribute_code']] = $attr['attribute_code'];
        }
		
		$requiredProperties = array('dob', 'gender');
		
		$this->logger->info('bxLog: get configured customer attributes for account: ' . $account);
		$filteredAttributes = $this->config->getAccountCustomersProperties($account, $attributes, $requiredProperties);
		
		foreach($attributes as $k => $attribute) {
			if(!in_array($attribute, $filteredAttributes)) {
				unset($attributes[$k]);
			}
		}
		$this->logger->info('bxLog: returning configured customer attributes for account ' . $account . ': ' . implode(',', array_values($attributes)));

		return $attributes;
    }

	/**
	 * @return array Delta Ids
	 */
	protected function checkForDeltaIds(){

		$db = $this->rs->getConnection();
		$ids = array();
		if($db->isTableExists('boxalino_indexer_cl') && $db->tableColumnExists('boxalino_indexer_cl' , 'entity_id')){

			$select = $db->select()->from(
				array('changelog' => $this->rs->getTableName('boxalino_indexer_cl')),
				'entity_id'
			)->group('entity_id');

			foreach($db->fetchAll($select) as $r){
				if(!isset($r)){
					continue;
				}
				$ids[] = $r['entity_id'];;
			}
			return $ids;
		}
		return array();
	}

	protected function clearDeltaIds(){
		$this->deltaIds;
		$db = $this->rs->getConnection();
		foreach ($this->deltaIds as $id){
			$db->delete('boxalino_indexer_cl', 'entity_id =' . $id);
		}
	}

	protected function getLastIndex(){
		return $this->boxalinoIndexer->getResetDate();
	}

    /**
     * @return string Index type
     */
    protected function getIndexType()
    {
        return sizeof($this->deltaIds) == 0 ? 'full' : 'delta';
    }

    /**
     * @description Preparing transactions to export
     * @return void
     */
    protected function exportTransactions($account, $files)
    {
        // don't export transactions in delta sync or when disabled
        if(!$this->config->isTransactionsExportEnabled($account)) {
			return;
		}

        $this->logger->info('bxLog: starting transaction export for account ' . $account);

        $db = $this->rs->getConnection();
        $limit = 1000;
        $count = $limit;
        $page = 1;
        $header = true;
		$transactions_to_save = array();
		// We use the crypt key as salt when generating the guest user hash
        // this way we can still optimize on those users behaviour, whitout
        // exposing any personal data. The server salt is there to guarantee
        // that we can't connect guest user profiles across magento installs.
        $salt = $db->quote(
            ((string) $this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_CRYPT_KEY)) .
            $account
        );

        while ($count >= $limit) {
			$this->logger->info('bxLog: Transactions - load page ' . $page . ' for account ' . $account);

            $configurable = array();
            $select = $db
                ->select()
                ->from(
                    array('order' => $this->rs->getTableName('sales_order')),
                    array(
                        'entity_id',
                        'status',
                        'updated_at',
                        'created_at',
                        'customer_id',
                        'base_subtotal',
                        'shipping_amount',
                    )
                )
                ->joinLeft(
                    array('item' => $this->rs->getTableName('sales_order_item')),
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
                    array('guest' => $this->rs->getTableName('sales_order_address')),
                    'order.billing_address_id = guest.entity_id',
                    array(
                        'guest_id' => 'IF(guest.email IS NOT NULL, SHA1(CONCAT(guest.email, ' . $salt . ')), NULL)'
                    )
                )
                ->where('order.status <> ?', 'canceled')
                ->order(array('order.entity_id', 'item.product_type'))
                ->limit($limit, ($page - 1) * $limit);
			if($this->getIndexType() == 'delta'){
				$select->where('order.created_at >= ?', $this->getLastIndex())->orWhere('order.updated_at >= ?', $this->getLastIndex());
			}

            $transaction_attributes = $this->getTransactionAttributes($account);
            if (count($transaction_attributes)) {
                $billing_columns = $shipping_columns = array();
                foreach ($transaction_attributes as $attribute) {
                    $billing_columns['billing_' . $attribute] = $attribute;
                    $shipping_columns['shipping_' . $attribute] = $attribute;
                }
                $select->joinLeft(
                           array('billing_address' => $this->rs->getTableName('sales_order_address')),
                           'order.billing_address_id = billing_address.entity_id',
                           $billing_columns
                       )
                       ->joinLeft(
                           array('shipping_address' => $this->rs->getTableName('sales_order_address')),
                           'order.shipping_address_id = shipping_address.entity_id',
                           $shipping_columns
					   );
            }

            $transactions = $db->fetchAll($select);


			$this->logger->info('bxLog: Transactions - loaded page ' . $page . ' for account ' . $account);

            foreach ($transactions as $transaction) {
                //is configurable
                if ($transaction['product_type'] == 'configurable') {
                    $configurable[$transaction['product_id']] = $transaction;
                    continue;
                }

                $productOptions = unserialize($transaction['product_options']);

                //is configurable - simple product
                if (intval($transaction['price']) == 0 && $transaction['product_type'] == 'simple' && isset($productOptions['info_buyRequest']['product'])) {
                    if (isset($configurable[$productOptions['info_buyRequest']['product']])) {
                        $pid = $configurable[$productOptions['info_buyRequest']['product']];

                        $transaction['original_price'] = $pid['original_price'];
                        $transaction['price'] = $pid['price'];
                    } else {
						$product = $this->productFactory->create();
						try {
							$product->load($productOptions['info_buyRequest']['product']);
							
							$transaction['original_price'] = ($product->getPrice());
							$transaction['price'] = ($product->getPrice());

							$tmp = array();
							$tmp['original_price'] = $transaction['original_price'];
							$tmp['price'] = $transaction['price'];

							$configurable[$productOptions['info_buyRequest']['product']] = $tmp;
							$tmp = null;
						} catch (\Exception $e) {
							$this->logger->critical($e);
						}
						$product = null;
                    }
                }

                $status = 0; // 0 - pending, 1 - confirmed, 2 - shipping
                if ($transaction['updated_at'] != $transaction['created_at']) {
                    switch ($transaction['status']) {
                        case 'canceled':
                            continue;
                            break;
                        case 'processing':
                            $status = 1;
                            break;
                        case 'complete':
                            $status = 2;
                            break;
                    }
                }

                $final_transaction = array(
                    'order_id' => $transaction['entity_id'],
                    'entity_id' => $transaction['product_id'],
                    'customer_id' => $transaction['customer_id'],
					'guest_id' => $transaction['guest_id'],
                    'price' => $transaction['original_price'],
                    'discounted_price' => $transaction['price'],
                    'quantity' => $transaction['qty_ordered'],
                    'total_order_value' => ($transaction['base_subtotal'] + $transaction['shipping_amount']),
                    'shipping_costs' => $transaction['shipping_amount'],
                    'order_date' => $transaction['created_at'],
                    'confirmation_date' => $status == 1 ? $transaction['updated_at'] : null,
                    'shipping_date' => $status == 2 ? $transaction['updated_at'] : null,
                    'status' => $transaction['status'],
                );

                if (count($transaction_attributes)) {
                    foreach ($transaction_attributes as $attribute) {
                        $final_transaction['billing_' . $attribute] = $transaction['billing_' . $attribute];
                        $final_transaction['shipping_' . $attribute] = $transaction['shipping_' . $attribute];
                    }
                }

                $transactions_to_save[] = $final_transaction;
				$guest_id_transaction = null;
				$final_transaction = null;
            }
            $data[] = $transactions_to_save;
			$count = count($transactions);
            $configurable = null;
            $transactions = null;

            if ($header) {
                $data = array_merge(array(array_keys(end($transactions_to_save))), $transactions_to_save);
                $header = false;
				$transactions_to_save = null;
            }

			$this->logger->info('bxLog: Transactions - save to file for account ' . $account);
            $files->savePartToCsv('transactions.csv', $data);
            $data = null;
			$page++;
        }

		$sourceKey = $this->bxData->setCSVTransactionFile($files->getPath('transactions.csv'), 'order_id', 'entity_id', 'customer_id', 'order_date', 'total_order_value', 'price', 'discounted_price');
		$this->bxData->addSourceCustomerGuestProperty($sourceKey,'guest_id');

		$this->logger->info('bxLog: Transactions - end of export for account ' . $account);
    }

	protected function exportProductAttributes($attrs = array(), $languages, $account, $files){

		$db = $this->rs->getConnection();
		$columns = array(
			'entity_id',
			'attribute_id',
			'value',
			'store_id'
		);
		$attrs['misc'][] = array('attribute_code' => 'categories');
		$files->prepareProductFiles($attrs);
		unset($attrs['misc']);

		foreach($attrs as $attrKey => $types){

			$select = $db->select()->from(
				array('t_d' => $this->rs->getTableName('catalog_product_entity_' . $attrKey)),
				$columns
			)->joinLeft(array('ea' => $this->rs->getTableName('eav_attribute')), 't_d.attribute_id = ea.attribute_id', array());
			$this->getIndexType() == 'delta' ? $select->where('t_d.entity_id IN(?)', $this->deltaIds) : '';

			foreach ($types as $typeKey => $type) {
				$data = array();
				$additionalData = array();
				$d = array();
				$mapping = array();

				foreach ($languages as $lang) {
					$labelColumns[$lang] = 'value_' . $lang;
					$storeObject = $this->config->getStore($account, $lang);
					$storeId = $storeObject->getId();
					$storeBaseUrl = $storeObject->getBaseUrl();
					if ($type['attribute_code'] == 'url_key') {
						if ($this->productMetaData->getEdition() != "Community") {
							$select1 = $db->select()
								->from(
									array('t_g' => $this->rs->getTableName('catalog_product_entity_url_key')),
									array('entity_id', 'attribute_id')
								)
								->joinLeft(
									array('t_s' => $this->rs->getTableName('catalog_product_entity_url_key')),
									't_s.attribute_id = t_g.attribute_id AND t_s.entity_id = t_g.entity_id',
									array('value' => 'IF(t_s.store_id IS NULL, t_g.value, t_s.value)')
								)
								->where('t_g.attribute_id = ?', $typeKey)->where('t_g.store_id = 0 OR t_g.store_id = ?', $storeId);
							$this->getIndexType() == 'delta' ? $select1->where('t_g.entity_id IN(?)', $this->deltaIds) : '';

							foreach ($db->fetchAll($select1) as $r) {
								$data[] = $r;
							}
							continue;
						}
					}
					$whereClause = clone $select;
					$whereClause->where('t_d.attribute_id = ?', $typeKey)->where('t_d.store_id = ? OR t_d.store_id = 0', $storeId);
					$fetchedResult = $db->fetchAll($whereClause);

					if (sizeof($fetchedResult) > 0) {

						foreach ($fetchedResult as $row) {
							if (isset($data[$row['entity_id']]) && isset($mapping[$row['entity_id']])) {
								if($row['store_id'] > $mapping[$row['entity_id']]) {
									$data[$row['entity_id']]['value_' . $lang] = $row['value'];
									if(isset($additionalData[$row['entity_id']])){
										if ($type['attribute_code'] == 'url_key') {
											$url = $storeBaseUrl . $row['value'] . '.html';
											$additionalData[$row['entity_id']]['value_' . $lang] = $url;
										} else {
											$url = $storeBaseUrl . "pub/media/catalog/product" . $row['value'];
											$additionalData[$row['entity_id']]['value_' . $lang] = $url;
										}
									}
									$mapping[$row['entity_id']] = 0;
									continue;
								}
								$data[$row['entity_id']]['value_' . $lang] = $row['value'];

								if (isset($additionalData[$row['entity_id']])) {
									if ($type['attribute_code'] == 'url_key') {
										$url = $storeBaseUrl . $row['value'] . '.html';
										$additionalData[$row['entity_id']]['value_' . $lang] = $url;
									} else {
										$url = $storeBaseUrl . "pub/media/catalog/product" . $row['value'];
										$additionalData[$row['entity_id']]['value_' . $lang] = $url;
									}
								}
								continue;
							} else {
								if ($type['is_global'] > 0) {
									$data[$row['entity_id']] = array('entity_id' => $row['entity_id'], $type['attribute_code'] . '_id' => $row['attribute_id'], 'value' => $row['value']);
									continue;
								}
								if ($type['attribute_code'] == 'url_key') {
									if ($this->config->exportProductUrl($account)) {
										$url = $storeBaseUrl . $row['value'] . '.html';
										$additionalData[$row['entity_id']] = array('entity_id' => $row['entity_id'], 'value_' . $lang => $url);
									}
								}
								if ($type['attribute_code'] == 'image') {
									if ($this->config->exportProductImages($account)) {
										$url = $storeBaseUrl . "pub/media/catalog/product" . $row['value'];
										$additionalData[$row['entity_id']] = array('entity_id' => $row['entity_id'], 'value_' . $lang => $url);
									}
								}
								$data[$row['entity_id']] = array('entity_id' => $row['entity_id'], $type['attribute_code'] . '_id' => $row['attribute_id'], 'value_' . $lang => $row['value']);
								$mapping[$row['entity_id']] = $row['store_id'];
							}
						}
						if($type['is_global'] > 0){
							$labelColumns = null;
							break;
						}
					}
					$whereClause = null;
				}

				if (sizeof($data)) {
					if(sizeof($additionalData)){
						$d = array_merge(array(array_keys(end($additionalData))), $additionalData);
						if ($type['attribute_code'] == 'url_key') {
							$files->savepartToCsv('product_default_url.csv', $d);
							$sourceKey = $this->bxData->addCSVItemFile($files->getPath('product_default_url.csv'), 'entity_id');
							$this->bxData->addSourceLocalizedTextField($sourceKey, 'default_url', $labelColumns);

						} else {
							$files->savepartToCsv('product_cache_image_url.csv', $d);
							$sourceKey = $this->bxData->addCSVItemFile($files->getPath('product_cache_image_url.csv'), 'entity_id');
							$this->bxData->addSourceLocalizedTextField($sourceKey, 'cache_image_url',$labelColumns);
						}
					}
					$d = array_merge(array(array_keys(end($data))), $data);
					$files->savepartToCsv('product_' . $type['attribute_code'] . '.csv', $d);

					$fieldId = $this->bxGeneral->sanitizeFieldName($type['attribute_code']);
					$attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_' . $type['attribute_code'] . '.csv'), 'entity_id');
					switch($type['attribute_code']){
						case 'name':
							$this->bxData->addSourceTitleField($attributeSourceKey, $labelColumns);
							break;
						case 'description':
							$this->bxData->addSourceDescriptionField($attributeSourceKey, $labelColumns);
							break;
						case 'price':
							$this->bxData->addSourceListPriceField($attributeSourceKey, $type['attribute_code'] . '_id');
							break;
						case 'special_price':
							$this->bxData->addSourceDiscountedPriceField($attributeSourceKey, $type['attribute_code'] . '_id');
							break;
						case ($attrKey == ('int' || 'decimal')) && $type['is_global'] > 0:
							$this->bxData->addSourceNumberField($attributeSourceKey, $fieldId, 'value');
							break;
						case $attrKey == 'datetime':
							$this->bxData->addSourceDateField($attributeSourceKey, $fieldId, 'value');
							break;
						default:
							if(sizeof($labelColumns) > 0){
								$this->bxData->addSourceLocalizedTextField($attributeSourceKey, $fieldId, $labelColumns);
							}else{
								$this->bxData->addSourceStringField($attributeSourceKey, $fieldId, 'value');
							}
							break;
					}
				}
				$data = null;
				$additionalData = null;
				$mapping = null;
				$d = null;
				$labelColumns = null;
			}
		}
	}

	protected function exportProductInformation($files){
		$db = $this->rs->getConnection();
		$fieldId = 'status';
		//product stock
		$select = $db->select()
			->from(
				$this->rs->getTableName('cataloginventory_stock_status'),
				array(
					'entity_id' => 'product_id',
					'stock_status',
					'qty'
				)
			)
			->where('stock_id = ?', 1);
		$this->getIndexType() == 'delta' ? $select->where('product_id IN(?)', $this->deltaIds) : '';

		foreach ($db->fetchAll($select) as $r) {
			$data[] = $r;
		}
		$d = array_merge(array(array_keys(end($data))), $data);
		$files->savePartToCsv('product_stock.csv', $d);
		$data = null;
		$d = null;
		$attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_stock.csv'), 'entity_id');
		$this->bxData->addSourceNumberField($attributeSourceKey, $fieldId, 'qty');

		//product website
		$fieldId = 'website';
		$select = $db->select()
			->from(
				array('c_p_w' => $this->rs->getTableName('catalog_product_website')),
				array(
					'entity_id' => 'product_id',
					'website_id',
				)
			)->joinLeft(array('s_w' => $this->rs->getTableName('store_website')),
				's_w.website_id = c_p_w.website_id',
				array('s_w.name')
			);
		$this->getIndexType() == 'delta' ? $select->where('product_id IN(?)', $this->deltaIds) : '';

		foreach ($db->fetchAll($select) as $r) {
			$data[] = $r;
		}
		$d = array_merge(array(array_keys(end($data))), $data);
		$files->savePartToCsv('product_website.csv', $d);
		$data = null;
		$d = null;
		$attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_website.csv'), 'entity_id');
		$this->bxData->addSourceStringField($attributeSourceKey, $fieldId, 'name');

		//product super link
		$fieldId = 'parent';
		$select = $db->select()
			->from(
				$this->rs->getTableName('catalog_product_super_link'),
				array(
					'entity_id' => 'product_id',
					'parent_id',
					'link_id'
				)
			);
		$this->getIndexType() == 'delta' ? $select->where('product_id IN(?)', $this->deltaIds) : '';

		foreach ($db->fetchAll($select) as $r) {
			$data[] = $r;
		}
		if(sizeof($data) > 0) {
			$d = array_merge(array(array_keys(end($data))), $data);
			$files->savePartToCsv('product_parent.csv', $d);
			$attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_parent.csv'), 'entity_id');
			$this->bxData->addSourceNumberField($attributeSourceKey, $fieldId, 'link_id');
		}
		$data = null;
		$d = null;

		//product categories
		$select = $db->select()
			->from(
				$this->rs->getTableName('catalog_category_product'),
				array(
					'entity_id' => 'product_id',
					'category_id',
					'position'
				)
			);
		$this->getIndexType() == 'delta' ? $select->where('product_id IN(?)', $this->deltaIds) : '';

		foreach ($db->fetchAll($select) as $r) {
			$data[] = $r;
		}
		$d = array_merge(array(array_keys(end($data))), $data);
		$files->savePartToCsv('product_categories.csv', $d);
		$data = null;
		$d = null;

		//product link
		$select = $db->select()
			->from(
				array('pl'=> $this->rs->getTableName('catalog_product_link')),
				array(
					'product_id',
					'linked_product_id',
					'lt.code'
				)
			)
			->joinLeft(
				array('lt' => $this->rs->getTableName('catalog_product_link_type')),
				'pl.link_type_id = lt.link_type_id', array()
			)
			->where('lt.link_type_id = pl.link_type_id');
		$this->getIndexType() == 'delta' ? $select->where('product_id IN(?)', $this->deltaIds) : '';

		foreach($db->fetchAll($select) as $r){
			$data[] = $r;
		}
		$d = array_merge(array(array_keys(end($data))), $data);
		$files->savePartToCsv('product_links.csv', $d);
	}

    /**
     * @description Preparing products to export
     * @param array $languages language structure
     * @return void
     */
    protected function exportProducts($account, $files, $attributes)
	{
		$languages = $this->config->getAccountLanguages($account);

		$this->logger->info('bxLog: Products - start of export for account ' . $account);
		$attrs = $attributes;
		$this->logger->info('bxLog: Products - get info about attributes - before for account ' . $account);

		$db = $this->rs->getConnection();
		$select = $db->select()
			->from(
				array('main_table' => $this->rs->getTableName('eav_attribute')),
				array(
					'attribute_id',
					'attribute_code',
					'backend_type',
				)
			)
			->joinInner(
				array('additional_table' => $this->rs->getTableName('catalog_eav_attribute'), 'is_global'),
				'additional_table.attribute_id = main_table.attribute_id'
			)
			->where('main_table.entity_type_id = ?', $this->getEntityIdFor('catalog_product'))
			->where('main_table.attribute_code IN(?)', $attrs);

		$this->logger->info('bxLog: Products - connected to DB, built attribute info query for account ' . $account);

		$attrsFromDb = array(
			'int' => array(),
			'varchar' => array(),
			'text' => array(),
			'decimal' => array(),
			'datetime' => array(),
		);

		foreach ($db->fetchAll($select) as $r) {
			$type = $r['backend_type'];
			if (isset($attrsFromDb[$type])) {
				$attrsFromDb[$type][$r['attribute_id']] = array('attribute_code' => $r['attribute_code'], 'is_global' => $r['is_global']);
			}
		}

		$countMax = 1000000; //$this->_storeConfig['maximum_population'];
		$limit = 1000; //$this->_storeConfig['export_chunk'];
		$totalCount = 0;
		$page = 1;
		$header = true;

		while (true) {
			if ($countMax > 0 && $totalCount >= $countMax) {
				break;
			}

			$select = $db->select()
				->from(
					array('e' => $this->rs->getTableName('catalog_product_entity'))
				)
				->limit($limit, ($page - 1) * $limit);

			$this->getIndexType() == 'delta' ? $select->where('entity_id IN (?)', $this->deltaIds) : '';

			$data = array();
			$fetchedResult = $db->fetchAll($select);
			if(sizeof($fetchedResult) > 0 ){
				foreach ($fetchedResult as $r) {
					$data[$r['entity_id']] = $r;
					$totalCount++;
				}
			}else{
				break;
			}

			if ($header && count($data) > 0) {
				$data = array_merge(array(array_keys(end($data))), $data);
				$header = false;
			}

			$files->savePartToCsv('products.csv', $data);
			$data = null;
			$page++;
		}
		$this->bxData->addMainCSVItemFile($files->getPath('products.csv'), 'entity_id');
		$this->exportProductAttributes($attrsFromDb, $languages, $account, $files);
		$this->exportProductInformation($files);
	}
}