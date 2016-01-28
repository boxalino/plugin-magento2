<?php

namespace Boxalino\Exporter\Model\Indexer;

use Boxalino\Exporter\Helper\BxIndexStructure;
use Boxalino\Exporter\Helper\BxFiles;
use Boxalino\Exporter\Helper\BxGeneral;

use Magento\Indexer\Model\Indexer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
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
	
	protected $categoryCollection;
	protected $rs;
	
	protected $indexType;

    public function __construct(
        StoreManagerInterface $storeManager,
		LoggerInterface $logger,
		Filesystem $filesystem,
		\Magento\Catalog\Helper\Product\Flat\Indexer $productHelper,
		\Magento\Catalog\Model\ResourceModel\Category\Collection $categoryCollection,
		\Magento\Framework\App\ResourceConnection $rs
    )
    {
       $this->storeManager = $storeManager;
	   $this->logger = $logger;
	   $this->filesystem = $filesystem;
	   $this->productHelper = $productHelper;
	   $this->categoryCollection = $categoryCollection;
	   $this->rs = $rs;
	   
	   $this->bxGeneral = new BxGeneral();
    }

    public function executeRow($id){
		
		$this->indexType = "delta";
        echo "executeRow";
        exit;
    }

    public function executeList(array $ids){
		
		$this->indexType = "delta";
        echo "executeList";
        exit;
    }

    public function execute($ids){
		
		$this->indexType = "delta";
		
        echo "execute";
        exit;
    }

    public function executeFull(){
		
		$this->indexType = "full";
		
		$this->logger->info("bxLog: starting executeFull");
		
		$indexStructure = new BxIndexStructure($this->storeManager->getWebsites());
		$this->logger->info("bxLog: retrieve index structure: " . $indexStructure->toString());
		
		foreach ($indexStructure->getAccounts() as $account) {
			
			$this->logger->info("bxLog: initialize files on account " . $account);
            $files = new BxFiles($this->filesystem, $account);
			
			list($attributeValues, $attributesValuesByName, $categories) = $this->storeExport($account, $indexStructure, $files);
			
			continue;

            /*$this->logger->info('something with attributes - before');
            foreach ($this->_listOfAttributes as $k => $attr) {
                if (
                    !isset($this->_attributesValuesByName[$attr]) ||
                    (isset($this->_attrProdCount[$attr]) &&
                        $this->_attrProdCount[$attr])
                ) {
                    continue;
                } else {
                    unset($this->_attributesValuesByName[$attr]);
                    unset($this->_listOfAttributes[$k]);
                }
            }
			$this->logger->info('something with attributes - after');
			
            $file = $this->prepareFiles($data['categories'], $data['tags']);
            
			$this->logger->info('Push files');
            $this->pushXML($file);
            $this->pushZip($file);
            $this->logger->info('Files pushed');

            $this->_transformedCategories = array();
            $this->_transformedTags = array();
            $this->_transformedProducts = array();
            $this->_categoryParent = array();
            $this->_availableLanguages = array();
            $this->_attrProdCount = array();
            $this->_count = 0;*/
        }
		
		$this->logger->info("bxLog: finished executeFull");
	}
	
	

    /**
     * @description generate the data for the language scope
     * @param array $languages Array of languages to generate for this index
     * @return array Data prepared for save to file
     */
    protected function storeExport($account, $indexStructure, $files)
    {
		$this->logger->info('Preparing data for website start');
		
		$attributeValues = array();
		$attributesValuesByName = array();
		$categories = array();
		
        foreach ($indexStructure->getAccountLanguages($account) as $language) {
			
			$store = $indexStructure->getStore($account, $language);
			
			$this->logger->info('Start getStoreAttributes on store:' . $store->getId());
			$attributes = $this->getStoreAttributes($store);
			
			$this->logger->info('Start getStoreAttributesValues on store:' . $store->getId());
			list($attributeValues, $attributesValuesByName) = $this->getStoreAttributesValues($attributes, $store, $language, $attributeValues, $attributesValuesByName);
            
			$this->logger->info('Start exportCategories on store:' . $store->getId());
			$categories = $this->exportCategories($store, $language, $categories);
        }
		
		$this->exportCustomers($account, $indexStructure, $files);
		
		$this->exportTransactions($account, $indexStructure, $files);
		
		//$this->exportProducts($account, $indexStructure, $files);

        return array($attributeValues, $attributesValuesByName, $categories);
    }

    /**
     * @description Preparing categories to export
     * @return array Categories
     */
    protected function exportCategories($store, $language, $transformedCategories)
    {
		$this->logger->info('Categories are not loaded');
		$categories = $this->categoryCollection->setProductStoreId($store->getId())->setStoreId($store->getId())->addAttributeToSelect('*');
		$categories->clear();
		
        $this->logger->info('Categories are loaded');
		
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
		$this->logger->info('Categories are returned for data saving');
		return $transformedCategories;
    }

    /**
     * @description Merge default attributes with attributes added by user
     * @return void
     */
    protected function getStoreAttributes($store)
    {
		
		$attributes = array();
		foreach ($this->productHelper->getAttributes() as $attribute) {
			$attributes[$attribute->getId()] = $attribute->getAttributeCode();
		}
		return $attributes;
		
		/*
        $this->_listOfAttributes = array(
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

        $attributes = array();

        foreach (Mage::getResourceModel('catalog/product_attribute_collection')->getItems() as $at) {
            $attributes[] = $at->getAttributeCode();
        }

        if (isset($this->_storeConfig['additional_attributes']) && $this->_storeConfig['additional_attributes'] != '') {
            $fields = explode(',', $this->_storeConfig['additional_attributes']);
            foreach ($fields as $field) {

                if (!in_array($field, $attributes)) {
                    Mage::throwException("Attribute \"$field\" doesn't exist, please update your additional_attributes setting in the Boxalino Exporter settings!");
                }

                if ($field != null && strlen($field) > 0) {
                    $this->_listOfAttributes[] = $field;
                }

            }
            unset($fields);
        }*/

    }
	
	
    /**
     * @description Get labels for all Attributes where is optionsId = optionValue
     * @return void
     */
    protected function getStoreAttributesValues($attributeCodes, $store, $language, $attributeValues, $attributesValuesByName)
    {	
        foreach ($this->productHelper->getAttributes() as $attribute) {
			if(isset($attributeCodes[$attribute->getId()])) {
				$options = $attribute->setStoreId($store->getId())->getSource()->getAllOptions();
				foreach ($options as $option) {
                    if (!empty($option['value'])) {
						
						if(!isset($attributeValues[$store->getId()][$attribute->getAttributeCode()])) {
							$attributeValues[$store->getId()][$attribute->getAttributeCode()] = array();
						}
						
                        $attributeValues[$store->getId()][$attribute->getAttributeCode()][$option['value']] = $this->bxGeneral->escapeString($option['label']);

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
		
		return array($attributeValues, $attributesValuesByName);
    }
	
	

    /**
     * @description Preparing customers to export
     * @param Mage_Core_Model_Website $website
     * @return void
     *
     */
    protected function exportCustomers($account, $indexStructure, $files)
    {
		if(!$indexStructure->isCustomersExportEnabled($account)) {
			return;
		}

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
		
        $customer_attributes = $this->mergeCustomerAttributes(array('dob', 'gender'));

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
            $this->logger->info("Customers - load page $page");
            $customers_to_save = array();

            $customers = array();

            $select = $db->select()
                ->from(
                    $this->rs->getTableName('customer_entity'),
                    array('entity_id', 'created_at', 'updated_at')
                )
                ->limit($limit, ($page - 1) * $limit);

            $this->_getIndexType() == 'delta' ? $select->where('created_at >= ? OR updated_at >= ?', $this->_getLastIndex()) : '';

            foreach ($db->fetchAll($select) as $r) {
                $customers[$r['entity_id']] = array('id' => $r['entity_id']);
            }

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

            foreach ($db->fetchAll($select) as $r) {
                $customers[$r['entity_id']][$r['attribute_code']] = $r['value'];
            }

            $select = null;
            $select1 = null;
            $select2 = null;
            $select3 = null;
            $select4 = null;
            $selects = null;

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

            $this->logger->info('Customers - loaded page ' . $page);

            foreach ($customers as $customer) {
                $this->logger->info('Customers - Load billing address ');
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
            $this->logger->info('Customers - save to file');
            $files->savePartToCsv('customers.csv', $data);
            $data = null;

            $count = count($customers_to_save);
            $page++;

        } while ($count >= $limit);
        $customers = null;

        $this->logger->info('Customers - end of exporting');
    }

    /**
     * Fetch entity id for a entity type.
     *
     * @param string $entityType
     * @return null|string
     */
	protected $_entityIds = null;
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

    /**
     * @description Merge default customer attributes with customer attributes added by user
     * @param array $attributes optional, array to merge the user defined attributes into
     * @return array
     */
    protected function mergeCustomerAttributes($attributes = array())
    {
		return $attributes;
		/*
        if (isset($this->_storeConfig['additional_customer_attributes']) && $this->_storeConfig['additional_customer_attributes'] != '') {
            if(count($this->_customerAttributes) == 0) {
                foreach (Mage::getModel('customer/customer')->getAttributes() as $at) {
                    $this->_customerAttributes[] = $at->getAttributeCode();
                }
            }

            foreach (explode(',', $this->_storeConfig['additional_customer_attributes']) as $field) {
                if (!in_array($field, $this->_customerAttributes)) {
                    Mage::throwException("Customer attribute \"$field\" doesn't exist, please update your additional_customer_attributes setting in the Boxalino Exporter settings!");
                }
                if ($field != null && strlen($field) > 0 && !in_array($field, $attributes)) {
                    $attributes[] = $field;
                }
            }
        }
        return $attributes;*/
    }

    /**
     * @return string Index type
     */
    protected function _getIndexType()
    {
        $this->indexType;
    }
	
	protected function _getLastIndex() {
		throw new Exception("_getLastIndex is not the delta process anymore");
	}
	
	

    /**
     * @description Preparing transactions to export
     * @return void
     */
    protected function exportTransactions($account, $indexStructure, $files)
    {
        // don't export transactions in delta sync or when disabled
        if(!$indexStructure->isTransactionsExportEnabled($account)) {
			return;
		}

        $this->logger->info('Transactions - start of export');
        $db = $this->rs->getConnection();

        $limit = 1000;
        $count = $limit;
        $page = 1;
        $header = true;

        // We use the crypt key as salt when generating the guest user hash
        // this way we can still optimize on those users behaviour, whitout
        // exposing any personal data. The server salt is there to guarantee
        // that we can't connect guest user profiles across magento installs.
        $salt = "\"salt\""; /*$db->quote(
            ((string) Mage::getConfig()->getNode('global/crypt/key')) .
            $this->_storeConfig['di_username']
        );*/

        while ($count >= $limit) {
            $this->logger->info('Transactions - load page ' . $page);
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

            $transaction_attributes = array(); //explode(',', $this->_storeConfig['additional_transactions_attributes']);
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
            /*if (!$this->_storeConfig['export_transactions_full']) {
                $select->where('order.created_at >= ?', $this->_getLastIndex())
                       ->orWhere('order.updated_at >= ?', $this->_getLastIndex());
            }*/

            $transactions = $db->fetchAll($select);
            $this->logger->info("Transactions - loaded page $page");

            foreach ($transactions as $transaction) {
                //is configurable
                if ($transaction['product_type'] == 'configurable') {
                    $configurable[$transaction['product_id']] = $transaction;
                    continue;
                }

                $productOptions = unserialize($transaction['product_options']);

                //is configurable - simple product
                /*if (intval($transaction['price']) == 0 && $transaction['product_type'] == 'simple') {
                    if (isset($configurable[$productOptions['info_buyRequest']['product']])) {
                        $pid = $configurable[$productOptions['info_buyRequest']['product']];

                        $transaction['original_price'] = $pid['original_price'];
                        $transaction['price'] = $pid['price'];
                    } else {
                        $pid = Mage::getModel('catalog/product')->load($productOptions['info_buyRequest']['product']);

                        $transaction['original_price'] = 0; //($pid->getPrice());
                        $transaction['price'] = 0; //($pid->getPrice());

                        $tmp = array();
                        $tmp['original_price'] = $transaction['original_price'];
                        $tmp['price'] = $transaction['price'];

                        $configurable[$productOptions['info_buyRequest']['product']] = $tmp;

                        $pid = null;
                        $tmp = null;
                    }
                }*/

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

            $this->logger->info('Transactions - save to file');
            $files->savePartToCsv('transactions.csv', $data);
            $data = null;

            $page++;

        }

        $this->logger->info('Transactions - end of export');
    }
}