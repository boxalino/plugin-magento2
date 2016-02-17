<?php

namespace Boxalino\Exporter\Model\Indexer;

use Boxalino\Exporter\Helper\BxindexConfig;
use Boxalino\Exporter\Helper\BxFiles;
use Boxalino\Exporter\Helper\BxGeneral;
use Boxalino\Exporter\Helper\BxDataIntelligenceXML;

use Magento\Indexer\Model\Indexer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Block\Product\Context;

use \Psr\Log\LoggerInterface;

class BxExporter implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface{

	/**
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
     * @var Boxalino\Exporter\Helper\BxGeneral
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
     * @var Magento\Catalog\Model\ProductFactory;
     */
    protected $productFactory;
	
	/**
     * @var Magento\Catalog\Helper\Image;
     */
    protected $_imageHelper;
	
	/**
     * @var Magento\Catalog\Block\Product\Context;
     */
    protected $context;
	
	/**
     * @var \Magento\Framework\App\ResourceConnection
     */
	protected $rs;
	
	/**
	* @var Magento\Framework\App\ProductMetadata
	*/
	protected $productMetaData;
	
	/**
	* @var Magento\Catalog\Model\Product\Type\Price
	*/
	private $typePrice;
	
	/**
	* the list of ids to update, do a full export in case the delta is array is empty
	*/
	protected $deltaIds = array();
	
	/**
	* @var Boxalino\Exporter\Helper\BxIndexConfig : containing the access to the configuration of each store to export
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
	* keeps the information whether a product property has been set at least once in the product export
	* key: name of the property
	* value: if true ==> set at least once
	*/
	private $_attrProdCount = array();

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
		\Magento\Catalog\Model\Product\Type\Price $typePrice
    )
    {
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
		$this->exportStores();
	}
	
	protected function exportStores($deltaIds=array()) {
		
		$this->logger->info("bxLog: starting exportStores");
		
		$this->deltaIds = $deltaIds;
		
		$this->config = new BxIndexConfig($this->storeManager->getWebsites());
		$this->logger->info("bxLog: retrieved index config: " . $this->config->toString());
		
		foreach ($this->config->getAccounts() as $account) {
			
			$this->logger->info("bxLog: initialize files on account: " . $account);
            $files = new BxFiles($this->filesystem, $this->logger, $account, $this->config);
		
			$this->logger->info("bxLog: verify credentials for account: " . $account);
			$files->verifyCredentials();
			
			$this->logger->info('bxLog: Preparing the attributes and category data for each language of the account: ' . $account);
			$attributesValuesByName = array();
			$categories = array();
			$attributes = null;
			foreach ($this->config->getAccountLanguages($account) as $language) {
				
				$store = $this->config->getStore($account, $language);
				
				$this->logger->info('bxLog: Start getStoreProductAttributes for language . ' . $language . ' on store:' . $store->getId());
				$attributes = $this->getStoreProductAttributes($account, $store);
				
				$this->logger->info('bxLog: Start getStoreProductAttributesValues for language . ' . $language . ' on store:' . $store->getId());
				$attributesValuesByName = $this->getStoreProductAttributesValues($account, $attributes, $store, $language, $attributesValuesByName);
				
				$this->logger->info('bxLog: Start exportCategories for language . ' . $language . ' on store:' . $store->getId());
				$categories = $this->exportCategories($store, $language, $categories);
			}
			
			$this->logger->info('bxLog: Export the customers, transactions and product files for account: ' . $account);
			$customer_attributes = $this->exportCustomers($account, $files);
			$this->exportTransactions($account, $files);
			$this->exportProducts($account, $files, $attributes, $attributesValuesByName, $categories);
			
			$this->logger->info('bxLog: Remove unused attributes: ' . $account);
			list($attributes, $attributesValuesByName) = $this->removeUnusedAttributes($attributes, $attributesValuesByName);
			
			$this->logger->info('bxLog: Prepare the final files: ' . $account);
			$file = $files->prepareGeneralFiles($attributesValuesByName, $categories);
			
			$this->logger->info('bxLog: Prepare XML configuration file: ' . $account);
			$bxDIXML = new BXDataIntelligenceXML($account, $files, $this->config);
			$bxDIXML->createXML($file . '.xml', $attributes, $attributesValuesByName, $customer_attributes);
			
			$this->logger->info('bxLog: Prepare ZIP file with all the data files for account: ' . $account);
			$files->createZip($file . '.zip', $file . '.xml');
			
			try {
				$this->logger->info('bxLog: Push the XML configuration file to the Data Indexing server for account: ' . $account);
				$files->pushXML($file, $this->getIndexType() == 'delta');
			} catch(\Exception $e) {
				$value = @json_decode($e->getMessage(), true);
				if(isset($value['error_type_number']) && $value['error_type_number'] == 3) {
					$this->logger->info('bxLog: Try to push the XML file a second time, error 3 happens always at the very first time but not after: ' . $account);
					$files->pushXML($file, $this->getIndexType() == 'delta', 2);
				} else {
					throw $e;
				}
			}
			
			$this->logger->info('bxLog: Publish the configuration chagnes from the magento2 owner for account: ' . $account);
			$publish = $this->config->publishConfigurationChanges($account);
			$changes = $files->publishMagentoConfigChanges($file, $publish);
            if(sizeof($changes['changes']) > 0 && !$publish) {
				$this->logger->warn("changes in configuration detected butnot published as publish configuration automatically option has not been activated for account: " . $account);
			}
			
			$this->logger->info('bxLog: Push the Zip data file to the Data Indexing server for account: ' . $account);
			$files->pushZip($file, $this->getIndexType() == 'delta');
			
            $this->_attrProdCount = array();
			
            $this->logger->info('bxLog: Finished account: ' . $account);
        }
		
		$this->logger->info("bxLog: finished exportStores");
	}
	
	protected function removeUnusedAttributes($attributes, $attributesValuesByName) {
		foreach ($attributes as $k => $attr) {
			if (
				!isset($attributesValuesByName[$attr]) ||
				(isset($this->_attrProdCount[$attr]) &&
					$this->_attrProdCount[$attr])
			) {
				continue;
			} else {
				unset($attributesValuesByName[$attr]);
				unset($attributes[$k]);
			}
		}
		return array($attributes, $attributesValuesByName);
	}

    /**
     * @description Preparing categories to export
     * @return array Categories
     */
    protected function exportCategories($store, $language, $transformedCategories)
    {
		$this->logger->info('bxLog: starts loading categories for store: ' . $store->getId());
		$categories = $this->categoryCollection->setProductStoreId($store->getId())->setStoreId($store->getId())->addAttributeToSelect('*');
		$categories->clear();
		
		$this->logger->info('bxLog: prepare transformed categories for store: ' . $store->getId());
		foreach ($categories as $category) {
			if ($category->getParentId() == null) {
				continue;
			}

			if (isset($transformedCategories[$category->getId()])) {
				$transformedCategories[$category->getId()]['value_' . $language] = $this->bxGeneral->escapeString($category->getName());
			} else {
				$parentId = null;
				if ($category->getParentId() != 0) {
					$parentId = $category->getParentId();
				}
				$transformedCategories[$category->getId()] = array('category_id' => $category->getId(), 'parent_id' => $parentId, 'value_' . $language => $this->bxGeneral->escapeString($category->getName()));
			}
		}
		
		$this->logger->info('bxLog: returning transformed categories for store: ' . $store->getId());
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
     * @description Get labels for all Attributes where is optionsId = optionValue
     * @return void
     */
    protected function getStoreProductAttributesValues($account, $attributeCodes, $store, $language, $attributesValuesByName)
    {	
        foreach ($this->productHelper->getAttributes() as $attribute) {
			if(isset($attributeCodes[$attribute->getId()])) {
				$options = $attribute->setStoreId($store->getId())->getSource()->getAllOptions();
				foreach ($options as $option) {
                    if (!empty($option['value'])) {
						
						$value = intval($option['value']);
                        $name = 'value_' . $language;
						
                        if (!isset($attributesValuesByName[$attribute->getAttributeCode()])) {
							$attributesValuesByName[$attribute->getAttributeCode()] = array();
						}
						
						if (!isset($attributesValuesByName[$attribute->getAttributeCode()][$value])) {
							$attributesValuesByName[$attribute->getAttributeCode()][$value] = array($attribute->getAttributeCode() . '_id' => $value);
						}
						
						$attributesValuesByName[$attribute->getAttributeCode()][$value][$name] = $this->bxGeneral->escapeString($option['label']);
                    }
                }
                unset($options);
            }
        }
		
		return $attributesValuesByName;
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

        $this->logger->info('bxLog: Customers - end of exporting for account: ' . $account);
		
		return $customer_attributes;
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
            $transactions_to_save = array();
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
            // when in full transaction export mode, don't limit the query
			//TODO: ENABLE A MODE TO ONLY EXPORT THE TRANSACTIONS SINCE LAST EXPORT AND NOT ALL THE TRANSACTIONS
            /*if (!$this->_storeConfig['export_transactions_full']) {
                $select->where('order.created_at >= ?', $this->_getLastIndex())
                       ->orWhere('order.updated_at >= ?', $this->_getLastIndex());
            }*/

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
            }

            $data = $transactions_to_save;
            $count = count($transactions);

            $configurable = null;
            $transactions = null;

            if ($count == 0 && $header) {
                return;
            }

            if ($header) {
                $data = array_merge(array(array_keys(end($transactions_to_save))), $transactions_to_save);
                $header = false;
            }

			$this->logger->info('bxLog: Transactions - save to file for account ' . $account);
            $files->savePartToCsv('transactions.csv', $data);
            $data = null;

            $page++;

        }

		$this->logger->info('bxLog: Transactions - end of export for account ' . $account);
    }

    /**
     * @description Preparing products to export
     * @param array $languages language structure
     * @return void
     */
    protected function exportProducts($account, $files, $attributes, $attributesValuesByName, $transformedCategories)
    {
		$languages = $this->config->getAccountLanguages($account);
		
		$transformedProducts = array();
		
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
                array('additional_table' => $this->rs->getTableName('catalog_eav_attribute')),
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
                $attrsFromDb[$type][] = $r['attribute_id'];
            }
        }
		$this->logger->info('bxLog: Products - attributes preparing done for account ' . $account);

        $countMax = 1000000; //$this->_storeConfig['maximum_population'];
        $localeCount = 0;

        $limit = 1000; //$this->_storeConfig['export_chunk'];
        $count = $limit;
		$totalCount = 0;
        $page = 1;
        $header = true;

        //prepare files
		$tmpFiles = array_keys($attributesValuesByName);
        $tmpFiles[] = 'categories';
        $files->prepareProductFiles($tmpFiles);

        while ($count >= $limit) {
            if ($countMax > 0 && $totalCount >= $countMax) {
                break;
            }

            foreach ($languages as $lang) {
				
				$storeObject = $this->config->getStore($account, $lang);
                $storeId = $storeObject->getId();
                $storeBaseUrl = $storeObject->getBaseUrl();
                $storeCode = $storeObject->getCode();

				
				$this->logger->info('bxLog: Products - fetch products - before for account ' . $account . ' for languge ' . $lang);
                $select = $db->select()
                    ->from(
                        array('e' => $this->rs->getTableName('catalog_product_entity'))
                    )
                    ->limit($limit, ($page - 1) * $limit);

                $this->getIndexType() == 'delta' ? $select->where('entity_id IN (?)', $this->deltaIds) : '';

				$this->logger->info('bxLog: Products - fetch products - after for account ' . $account . ' for languge ' . $lang);

                $products = array();
                $ids = array();
                $count = 0;
                foreach ($db->fetchAll($select) as $r) {
                    $products[$r['entity_id']] = $r;
                    $ids[] = $r['entity_id'];
                    $products[$r['entity_id']]['website'] = array();
                    $products[$r['entity_id']]['categories'] = array();
                    $count++;
                }

                // we have to check for settings on the different levels: Store(View) & Global
				$this->logger->info('bxLog: Products - get attributes - before for account ' . $account . ' for languge ' . $lang);
                $columns = array(
                    'entity_id',
                    'attribute_id',
                );
                $joinCondition = $db->quoteInto('t_s.attribute_id = t_d.attribute_id AND t_s.entity_id = t_d.entity_id AND t_s.store_id = ?', $storeId);
                $joinColumns = array('value' => 'IF(t_s.value_id IS NULL, t_d.value, t_s.value)');

                $select1 = $db->select()
                    ->joinLeft(array('ea' => $this->rs->getTableName('eav_attribute')), 't_d.attribute_id = ea.attribute_id', 'ea.attribute_code')
                    ->where('t_d.store_id = ?', 0)
                    ->where('t_d.entity_id IN(?)', $ids);
                $select2 = clone $select1;
                $select3 = clone $select1;
                $select4 = clone $select1;
                $select5 = clone $select1;

                $select1->from(
                        array('t_d' => $this->rs->getTableName('catalog_product_entity_varchar')),
                        $columns
                    )
                    ->joinLeft(
                        array('t_s' => $this->rs->getTableName('catalog_product_entity_varchar')),
                        $joinCondition,
                        $joinColumns
                    )
                    ->where('t_d.attribute_id IN(?)', $attrsFromDb['varchar']);
                $select2->from(
                        array('t_d' => $this->rs->getTableName('catalog_product_entity_text')),
                        $columns
                    )
                    ->joinLeft(
                        array('t_s' => $this->rs->getTableName('catalog_product_entity_text')),
                        $joinCondition,
                        $joinColumns
                    )
                    ->where('t_d.attribute_id IN(?)', $attrsFromDb['text']);
                $select3->from(
                        array('t_d' => $this->rs->getTableName('catalog_product_entity_decimal')),
                        $columns
                    )
                    ->joinLeft(
                        array('t_s' => $this->rs->getTableName('catalog_product_entity_decimal')),
                        $joinCondition,
                        $joinColumns
                    )
                    ->where('t_d.attribute_id IN(?)', $attrsFromDb['decimal']);
                $select4->from(
                        array('t_d' => $this->rs->getTableName('catalog_product_entity_int')),
                        $columns
                    )
                    ->joinLeft(
                        array('t_s' => $this->rs->getTableName('catalog_product_entity_int')),
                        $joinCondition,
                        $joinColumns
                    )
                    ->where('t_d.attribute_id IN(?)', $attrsFromDb['int']);
                $select5->from(
                        array('t_d' => $this->rs->getTableName('catalog_product_entity_datetime')),
                        $columns
                    )
                    ->joinLeft(
                        array('t_s' => $this->rs->getTableName('catalog_product_entity_datetime')),
                        $joinCondition,
                        $joinColumns
                    )
                    ->where('t_d.attribute_id IN(?)', $attrsFromDb['datetime']);

                $select = $db->select()->union(
                    array($select1, $select2, $select3, $select4, $select5),
                    \Magento\Framework\DB\Select::SQL_UNION_ALL
                );

                $select1 = null;
                $select2 = null;
                $select3 = null;
                $select4 = null;
                $select5 = null;
                foreach ($db->fetchAll($select) as $r) {
                    $products[$r['entity_id']][$r['attribute_code']] = $r['value'];
                }
				$this->logger->info('bxLog: Products - get attributes - after for account ' . $account . ' for languge ' . $lang);

				$this->logger->info('bxLog: Products - get stock - before for account ' . $account . ' for languge ' . $lang);
                $select = $db->select()
                    ->from(
                        $this->rs->getTableName('cataloginventory_stock_status'),
                        array(
                            'product_id',
                            'stock_status',
                        )
                    )
                    ->where('stock_id = ?', 1)
                    ->where('website_id = ?', 1)
                    ->where('product_id IN(?)', $ids);
                foreach ($db->fetchAll($select) as $r) {
                    $products[$r['product_id']]['stock_status'] = $r['stock_status'];
                }
				$this->logger->info('bxLog: Products - get stock - after for account ' . $account . ' for languge ' . $lang);

				$this->logger->info('bxLog: Products - get products from website - before for account ' . $account . ' for languge ' . $lang);
                $select = $db->select()
                    ->from(
                        $this->rs->getTableName('catalog_product_website'),
                        array(
                            'product_id',
                            'website_id',
                        )
                    )
                    ->where('product_id IN(?)', $ids);
                foreach ($db->fetchAll($select) as $r) {
                    $products[$r['product_id']]['website'][] = $r['website_id'];
                }
				$this->logger->info('bxLog: Products - get products from website - after for account ' . $account . ' for languge ' . $lang);

				$this->logger->info('bxLog: Products - get products connections - before for account ' . $account . ' for languge ' . $lang);
                $select = $db->select()
                    ->from(
                        $this->rs->getTableName('catalog_product_super_link'),
                        array(
                            'product_id',
                            'parent_id',
                        )
                    )
                    ->where('product_id IN(?)', $ids);
                foreach ($db->fetchAll($select) as $r) {
                    $products[$r['product_id']]['parent_id'] = $r['parent_id'];
                }
				$this->logger->info('bxLog: Products - get products connections - after for account ' . $account . ' for languge ' . $lang);

				$this->logger->info('bxLog: Products - get categories - before for account ' . $account . ' for languge ' . $lang);
                $select = $db->select()
                    ->from(
                        $this->rs->getTableName('catalog_category_product'),
                        array(
                            'product_id',
                            'category_id',
                        )
                    )
                    ->where('product_id IN(?)', $ids);
                foreach ($db->fetchAll($select) as $r) {
                    $products[$r['product_id']]['categories'][] = $r['category_id'];
                }
                $select = null;
				$this->logger->info('bxLog: Products - get categories - after for account ' . $account . ' for languge ' . $lang);

				if ($this->productMetaData->getEdition() != "Community") {
					$this->logger->info('bxLog: Products - get EE URL key  - before for account ' . $account . ' for languge ' . $lang);
                    $select = $db->select()
                        ->from(
                            array('t_g' => $this->rs->getTableName('catalog_product_entity_url_key')),
                            array('entity_id')
                        )
                        ->joinLeft(
                            array('t_s' => $this->rs->getTableName('catalog_product_entity_url_key')),
                            $db->quoteInto('t_s.attribute_id = t_g.attribute_id AND t_s.entity_id = t_g.entity_id AND t_s.store_id = ?', $storeId),
                            array('value' => 'IF(t_s.store_id IS NULL, t_g.value, t_s.value)')
                        )
                        ->where('t_g.store_id = ?', 0)
                        ->where('t_g.entity_id IN(?)', $ids);
                    foreach ($db->fetchAll($select) as $r) {
                        $products[$r['entity_id']]['url_key'] = $r['value'];
                    }
					$this->logger->info('bxLog: Products - get EE URL key  - after for account ' . $account . ' for languge ' . $lang);
                }
                $select = null;

                $select = $db->select()
                    ->from(
                        array('pl'=> $this->rs->getTableName('catalog_product_link')),
                        array(
                            'link_id',
                            'product_id',
                            'linked_product_id',
                            'link_type_id'
                        )
                    )
					->from(
                        array('lt' => $this->rs->getTableName('catalog_product_link_type')),
                        array(
                            'link_type_id',
                            'code'
                        )
                    )
                    ->where('lt.link_type_id = pl.link_type_id')
                    ->where('product_id IN(?)', $ids);
                $linkCodes = array();
				foreach ($db->fetchAll($select) as $r) {
					$linkCodes[$r['code']] = 'linked_products_' . $r['code'];
				    if(!isset($products[$r['product_id']]['linked_products_' . $r['code']])) {
						$products[$r['product_id']]['linked_products_' . $r['code']] = $r['linked_product_id'];
					} else {
						$products[$r['product_id']]['linked_products_' . $r['code']] .= ',' . $r['linked_product_id'];
					}
                }
				foreach($linkCodes as $code => $codeFieldName) {
					$attrs[] = $codeFieldName;
				}
                $ids = null;

                foreach ($products as $product) {
					$this->logger->info('bxLog: Products - start transform for account ' . $account . ' for languge ' . $lang);

					// TODO: FIGURE OUT HOW THE GROUP LOGIC WORKS EXACTLY
                    if (count($product['website']) == 0) { // || !in_array($storeObject->getGroupId(), $product['website'])) {
                        $product = null;
                        continue;
                    }

                    $id = $product['entity_id'];
                    $productParam = array();
                    $haveParent = false;

                    if (array_key_exists('parent_id', $product)) {
                        $id = $product['parent_id'];
                        $haveParent = true;
                    }
					
					if(!isset($product['special_from_date'])) {
						$product['special_from_date'] = null;
					}
					if(!isset($product['special_to_date'])) {
						$product['special_to_date'] = null;
					}

                    // apply special price time range
                    if (
                        !empty($product['special_price']) &&
                        $product['price'] > $product['special_price'] && (
                            !empty($product['special_from_date']) ||
                            !empty($product['special_to_date'])
                        )
                    ) {
                        $product['special_price'] = $this->typePrice->calculateSpecialPrice(
                            $product['price'],
                            $product['special_price'],
                            $product['special_from_date'],
                            $product['special_to_date'],
                            $storeObject
                        );
                    }

                    foreach ($attrs as $attr) {
						$this->logger->info('bxLog: Products - start attributes transform for attribute $attr for account ' . $account . ' for languge ' . $lang);
                        
                        if (isset($attributesValuesByName[$attr])) {

                            $val = array_key_exists($attr, $product) ? $this->bxGeneral->escapeString($product[$attr]) : '';
                            if ($val == null) {
                                continue;
                            }

                            $attr = $this->bxGeneral->sanitizeFieldName($attr);

                            $this->_attrProdCount[$attr] = true;

                            // visibility as defined in Mage_Catalog_Model_Product_Visibility:
                            // 4 - VISIBILITY_BOTH
                            // 3 - VISIBILITY_IN_SEARCH
                            // 2 - VISIBILITY_IN_CATALOG
                            // 1 - VISIBILITY_NOT_VISIBLE
                            // status as defined in Mage_Catalog_Model_Product_Status:
                            // 2 - STATUS_DISABLED
                            // 1 - STATUS_ENABLED
                            if ($attr == 'visibility' || $attr == 'status') {
                                $productParam[$attr . '_' . $lang] = $val;
                            } else {
								$files->addToCSV($attr, array($id, $val));
                            }

                            $val = null;
                            continue;
                        }

                        $val = array_key_exists($attr, $product) ? $this->bxGeneral->escapeString($product[$attr]) : '';
                        switch ($attr) {
                            case 'category_ids':
                                break;
                            case 'description':
                            case 'short_description':
                            case 'name':
                            case 'status':
                                $productParam[$attr . '_' . $lang] = $val;
                                break;
                            default:
                                $productParam[$attr] = $val;
                                break;
                        }
						$this->logger->info('bxLog: Products - end attributes transform for attribute $attr for account ' . $account . ' for languge ' . $lang);

                    }

                    if ($haveParent) {
                        $product = null;
                        continue;
                    }

                    if (!isset($transformedProducts['products'][$id])) {
                        if ($countMax > 0 && $totalCount >= $countMax) {
                            $product = null;
                            $products = null;
                            break;
                        }
                        $productParam['entity_id'] = $id;
                        $transformedProducts['products'][$id] = $productParam;

                        // Add categories
                        if (isset($product['categories']) && count($product['categories']) > 0) {
                            foreach ($product['categories'] as $cat) {

                                while ($cat != null) {
									
									$files->addToCSV('categories', array($id, $cat));
                                    if (isset($transformedCategories[$cat]['parent_id'])) {
                                        $cat = $transformedCategories[$cat]['parent_id'];
                                    } else {
                                        $cat = null;
                                    }
                                }
                            }
                        }
                        $totalCount++;
                        $localeCount++;

                        // Add url to image cache
                        if ($this->config->exportProductImages($account)) {
							
							$product = $this->productFactory->create();
							try {
								$product->load($id);
								$media_gallery = $product->getMediaGallery();
								foreach ($media_gallery['images'] as $image) {
									
									//TODO: CORRECT THAT IT RETRIEVES A PROPER URL WHICH CAN BE RE-USED
									$url = $this->_imageHelper->init($product, $image['file'])->getUrl();
									$url_tbm = $this->_imageHelper->init($product, $image['file'])->resize(100)->getUrl();

									$this->_productsImages[] = array($id, $url);
									$this->_productsThumbnails[] = array($id, $url_tbm);
								}
							
							} catch (\Exception $e) {
								$this->logger->critical($e);
							}
							$product = null;
                        }

                    } elseif (isset($transformedProducts['products'][$id])) {
                        $transformedProducts['products'][$id] = array_merge($transformedProducts['products'][$id], $productParam);
                    }

                    // Add url to product for each languages
                    if ($this->config->exportProductUrl($account)) {
                        if (array_key_exists('url_key', $product)) {
                            $url_path = $product['url_key'] . '.html';
                        } else {
                            $url_path = $this->bxGeneral->rewrittenProductUrl(
                                $id, $storeId
                            );
                        }
                        $transformedProducts['products'][$id] = array_merge(
                            $transformedProducts['products'][$id],
                            array('default_url_' . $lang => (
                                $storeBaseUrl . $url_path . '?___store=' . $storeCode
                            ))
                        );
                    }

                    $productParam = null;
                    $product = null;

                    ksort($transformedProducts['products'][$id]);
					$this->logger->info('bxLog: Products - end transform for account ' . $account . ' for languge ' . $lang);
                }
            }

            if (isset($transformedProducts['products']) && count($transformedProducts['products']) > 0) {
				
				$this->logger->info('bxLog: Products - validate names start for account ' . $account);

                $data = $transformedProducts['products'];

                if ($header && count($data) > 0) {
                    $data = array_merge(array(array_keys(end($data))), $data);
                    $header = false;
                }
				$this->logger->info('bxLog: Products - save to file for account ' . $account);
                $files->savePartToCsv('products.csv', $data);
                $data = null;
                $transformedProducts['products'] = null;
                $transformedProducts['products'] = array();

                if ($this->config->exportProductImages($account)) {
					$this->logger->info('bxLog: Products - save images for account ' . $account);

                    $d = $this->_productsImages;
                    $this->savePartToCsv('product_cache_image_url.csv', $d);
                    $d = null;

                    $d = $this->_productsThumbnails;
                    $this->savePartToCsv('product_cache_image_thumbnail_url.csv', $d);
                    $d = null;
                    $this->_productsImages = array();
                    $this->_productsThumbnails = array();
                }

            }

            $page++;

            $products = null;

        }

        $attrFDB = null;
        $attrsFromDb = null;
        $attrs = null;
        $transformedProducts = null;
        $db = null;
		
		$files->closeFiles($tmpFiles);
	}
}