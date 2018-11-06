<?php
namespace Boxalino\Intelligence\Model\Indexer;

use Boxalino\Intelligence\Helper\BxIndexConfig;
use Boxalino\Intelligence\Helper\BxFiles;
use Boxalino\Intelligence\Helper\BxGeneral;
use Boxalino\Intelligence\Model\ResourceModel\Exporter as ExporterResource;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Catalog\Model\ProductFactory;

use Zend\Server\Exception\RuntimeException;
use \Psr\Log\LoggerInterface;

/**
 * Class BxIndexer
 * @package Boxalino\Intelligence\Model\Indexer
 */
class BxIndexer
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Boxalino\Intelligence\Helper\BxGeneral
     */
    protected $bxGeneral;

    /**
     * @var BxFiles
     */
    protected $bxFiles;

    /**
     * @var \Magento\Framework\App\DeploymentConfig
     */
    protected $deploymentConfig;

    /**
     * @var \Magento\Catalog\Model\ProductFactory;
     */
    protected $productFactory;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $rs;

    /**
     * @var \Boxalino\Intelligence\Helper\BxIndexConfig : containing the access to the configuration of each store to export
     */
    private $config = null;

    /**
     * @var null
     */
    private $bxData = null;

    /**
     * @var []
     */
    protected $deltaIds = null;

    /**
     * @var string
     */
    protected $indexerType = 'full';

    /**
     * @var \Magento\Indexer\Model\Indexer
     */
    protected $indexerModel;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $countryFactory;

    /**
     * @var ExporterResource
     */
    protected $exporterResource;

    /**
     * BxIndexer constructor.
     * @param LoggerInterface $logger
     * @param \Magento\Framework\App\ResourceConnection $rs
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param ProductFactory $productFactory
     * @param \Magento\Framework\App\ProductMetadata $productMetaData
     * @param \Magento\Indexer\Model\Indexer $indexer
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     */
    public function __construct(
        LoggerInterface $logger,
        BxFiles $bxFiles,
        BxGeneral $bxGeneral,
        BxIndexConfig $bxIndexConfig,
        \Magento\Framework\App\ResourceConnection $rs,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        ProductFactory $productFactory,
        \Magento\Framework\App\ProductMetadata $productMetaData,
        \Magento\Indexer\Model\Indexer $indexer,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        ExporterResource $exporterResource
    )
    {
        $this->bxFiles = $bxFiles;
        $this->exporterResource = $exporterResource;
        $this->countryFactory = $countryFactory;
        $this->indexerModel = $indexer;
        $this->logger = $logger;
        $this->rs = $rs;
        $this->deploymentConfig = $deploymentConfig;
        $this->productFactory = $productFactory;
        $this->productMetaData = $productMetaData;
        $this->bxGeneral = $bxGeneral;
        $this->config = $bxIndexConfig;

        $libPath = __DIR__ . '/../../Lib';
        require_once($libPath . '/BxClient.php');
        \com\boxalino\bxclient\v1\BxClient::LOAD_CLASSES($libPath);
    }

    /**
     * @param null $type
     * @return $this
     */
    public function setIndexerType($type=null){

        $this->indexerType = $type;
        if($type == null){
            $this->indexerType = 'full';
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIndexerType(){

        return $this->indexerType;
    }

    /**
     * @return mixed
     */
    protected function getDeltaIds(){

        return $this->deltaIds;
    }

    /**
     * @param $ids
     */
    public function setDeltaIds($ids){

        $this->deltaIds = $ids;
        return $this;
    }

    /**
     * @param bool $exportProducts
     * @param bool $exportCustomers
     * @param bool $exportTransactions
     */
    public function exportStores($exportProducts=true, $exportCustomers=true, $exportTransactions=true)
    {
        if(($this->getIndexerType() == 'delta') &&  $this->indexerModel->load('boxalino_indexer')->isWorking()){
            $this->logger->info("bxLog: Delta exporter will not run. Full exporter process must finish first.");
            return;
        }
        if(($this->getIndexerType() == 'full') &&  $this->indexerModel->load('boxalino_indexer_delta')->isWorking()){
            $this->logger->info("bxLog: Full exporter will not run. Delta exporter process must to finish first.");
            return;
        }

        if($this->getIndexerType() == 'delta'){
            if($this->getDeltaIds() == null){
                $this->logger->info("bxLog: The delta export is empty. Closing request.");
                return;
            }
        }
        $this->logger->info("bxLog: starting Boxalino {$this->indexerType} export");
        $this->logger->info("bxLog: retrieved index config: " . $this->config->toString());
        try {
            foreach ($this->config->getAccounts() as $account)
            {
                $this->logger->info("bxLog: initialize files on account: " . $account);
                $this->bxFiles->setAccount($account);

                $bxClient = new \com\boxalino\bxclient\v1\BxClient($account, $this->config->getAccountPassword($account), "", $this->config->isAccountDev($account));
                $this->bxData = new \com\boxalino\bxclient\v1\BxData($bxClient, $this->config->getAccountLanguages($account), $this->config->isAccountDev($account), $this->getIndexerType() == 'delta');

                $this->logger->info("bxLog: verify credentials for account: " . $account);
                try{
                    $this->bxData->verifyCredentials();
                } catch (\Exception $e){
                    $this->logger->info($e);
                    throw $e;
                }

                $this->logger->info('bxLog: Export the customers, transactions and product files for account: ' . $account);
                if($exportProducts) {$exportProducts = $this->exportProducts($account);}
                if($this->getIndexerType() == 'full'){
                    if($exportCustomers){ $this->exportCustomers($account);}
                    if($exportTransactions){ $this->exportTransactions($account);}
                }

                $this->logger->info('bxLog: Preparing category data for each language of the account: ' . $account);
                $categories = [];
                foreach ($this->config->getAccountLanguages($account) as $language) {
                    $store = $this->config->getStore($account, $language);
                    $this->logger->info('bxLog: Start exportCategories for language . ' . $language . ' on store:' . $store->getId());
                    $categories = $this->exportCategories($store, $language, $categories);
                }
                $this->prepareData($account, $categories);
                $this->logger->info('bxLog: Categories exported.');

                if(!$exportProducts)
                {
                    $this->logger->info('bxLog: No Products found for account: ' . $account);
                    $this->logger->info('bxLog: Finished account: ' . $account);
                } else {
                    if($this->getIndexerType() == 'full')
                    {
                        $this->logger->info('bxLog: Prepare the final files: ' . $account);
                        $this->logger->info('bxLog: Prepare XML configuration file: ' . $account);

                        try {
                            $this->logger->info('bxLog: Push the XML configuration file to the Data Indexing server for account: ' . $account);
                            $this->bxData->pushDataSpecifications();
                        } catch(\Exception $e) {
                            $value = @json_decode($e->getMessage(), true);
                            if(isset($value['error_type_number']) && $value['error_type_number'] == 3)
                            {
                                $this->logger->warning('bxLog: Try to push the XML file a second time, error 3 happens always at the very first time but not after: ' . $account);
                                $this->bxData->pushDataSpecifications();
                            } else {
                                throw $e;
                            }
                        }

                        $this->logger->info('bxLog: Publish the configuration chagnes from the magento2 owner for account: ' . $account);
                        $publish = $this->config->publishConfigurationChanges($account);
                        $changes = $this->bxData->publishOwnerChanges($publish);
                        if(sizeof($changes['changes']) > 0 && !$publish)
                        {
                            $this->logger->warning("changes in configuration detected butnot published as publish configuration automatically option has not been activated for account: " . $account);
                        }
                        $this->logger->info('bxLog: Push the Zip data file to the Data Indexing server for account: ' . $account);

                    }
                    $this->logger->info('pushing to DI');
                    try {
                        $this->bxData->pushData(null , $this->getTimeoutForExporter($account));
                    } catch(LocalizedException $e){
                        $this->logger->warning($e->getMessage());
                    } catch(\Exception $e){
                        $this->logger->error($e);
                        throw $e;
                    }
                    $this->logger->info('bxLog: Finished account: ' . $account);
                }
            }
            $this->logger->info("bxLog: finished Boxalino {$this->indexerType} export");
        } catch(\Exception $e) {
            $this->logger->error("bxLog: failed with exception: " . $e->getMessage());
        }
    }

    /**
     * Get timeout for exporter
     * @return bool|int
     */
    protected function getTimeoutForExporter($account)
    {
        if($this->indexerType == "delta")
        {
            return 60;
        }

        $customTimeout = $this->config->getExporterTimeout($account);
        if($customTimeout)
        {
            return $customTimeout;
        }

        return 3000;
    }

    /**
     * @param $account
     * @param $categories
     * @param null $tags
     * @param null $productTags
     */
    protected function prepareData($account, $categories, $tags = null, $productTags = null)
    {
        $withTag = ($tags != null && $productTags != null) ? true : false;
        $languages = $this->config->getAccountLanguages($account);
        $categories = array_merge(array(array_keys(end($categories))), $categories);
        $this->bxFiles->savePartToCsv('categories.csv', $categories);
        $labelColumns = array();
        foreach ($languages as $lang) {
            $labelColumns[$lang] = 'value_' . $lang;
        }
        $this->bxData->addCategoryFile($this->bxFiles->getPath('categories.csv'), 'category_id', 'parent_id', $labelColumns);
        $productToCategoriesSourceKey = $this->bxData->addCSVItemFile($this->bxFiles->getPath('product_categories.csv'), 'entity_id');
        $this->bxData->setCategoryField($productToCategoriesSourceKey, 'category_id');
    }

    /**
     * @param $store
     * @param $language
     * @param $transformedCategories
     * @return mixed
     * @throws \Exception
     */
    protected function exportCategories($store, $language, $transformedCategories)
    {
        $categories = $this->exporterResource->getCategoriesByStoreId($store->getId());
        foreach($categories as $r){
            if (!$r['parent_id'])  {
                continue;
            }
            if(isset($transformedCategories[$r['entity_id']])) {
                $transformedCategories[$r['entity_id']]['value_' .$language] = $r['value'];
                continue;
            }
            $transformedCategories[$r['entity_id']] = ['category_id' => $r['entity_id'], 'parent_id' => $r['parent_id'], 'value_' . $language => $r['value']];
        }

        return $transformedCategories;
    }

    /**
     * @param $account
     * @param $store
     * @return array
     */
    protected function getStoreProductAttributes($account)
    {
        $this->logger->info('bxLog: get all product attributes.');
        $attributes = $this->exporterResource->getProductAttributes();

        $requiredProperties = [
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
        ];

        $this->logger->info('bxLog: get configured product attributes.');
        $attributes = $this->config->getAccountProductsProperties($account, $attributes, $requiredProperties);
        $this->logger->info('bxLog: returning configured product attributes: ' . implode(',', array_values($attributes)));

        return $attributes;
    }

    /**
     * @param $account
     * @throws \Zend_Db_Select_Exception
     */
    protected function exportCustomers($account){

        if(!$this->config->isCustomersExportEnabled($account)) {
            return;
        }

        $this->logger->info('bxLog: starting exporting customers for account: ' . $account);
        $countryHelper = $this->countryFactory->create();
        $limit = 1000;
        $count = $limit;
        $page = 1;
        $header = true;
        $attrsFromDb = ['int'=>[], 'static'=>[], 'varchar'=>[], 'datetime'=>[]];

        $this->logger->info('bxLog: get final customer attributes for account: ' . $account);
        $customer_attributes = $this->getCustomerAttributes($account);
        $this->logger->info('bxLog: get customer attributes backend types for account: ' . $account);

        $result = $this->exporterResource->getCustomerAttributesByCodes($customer_attributes);
        foreach ($result as $attr) {
            if (isset($attrsFromDb[$attr['backend_type']])) {
                $attrsFromDb[$attr['backend_type']][] = $attr['backend_type'] == 'static' ? $attr['attribute_code'] : $attr['aid'];
            }
        }

        $fieldsForCustomerSelect =  array_merge(['entity_id', 'confirmation'], $attrsFromDb['static']);
        do {
            $this->logger->info('bxLog: Customers - load page $page for account: ' . $account);
            $customers_to_save = [];

            $this->logger->info('bxLog: Customers - get customer ids for page $page for account: ' . $account);
            $customers = $this->exporterResource->getCustomerAddressByFieldsAndLimit($limit, $page, $fieldsForCustomerSelect);

            $this->logger->info('bxLog: Customers - prepare side queries page $page for account: ' . $account);
            $ids = array_keys($customers);
            $customerAttributesValues = $this->exporterResource->getUnionCustomerAttributesByAttributesAndIds($attrsFromDb, $ids);
            if(!empty($customerAttributesValues))
            {
                $this->logger->info('bxLog: Customers - retrieve data for side queries page $page for account: ' . $account);
                foreach ($customerAttributesValues as $r) {
                    $customers[$r['entity_id']][$r['attribute_code']] = $r['value'];
                }
            }

            $this->logger->info('bxLog: Customers - load data per customer for page $page for account: ' . $account);
            foreach ($customers as $customer) {
                $id = $customer['entity_id'];
                $countryCode = $customer['country_id'];
                if (array_key_exists('gender', $customer)) {
                    if ($customer['gender'] % 2 == 0) {
                        $customer['gender'] = 'female';
                    } else {
                        $customer['gender'] = 'male';
                    }
                }
                $customer_to_save = array(
                    'customer_id' => $id,
                    'country' => !empty($countryCode) ? $countryHelper->loadByCode($countryCode)->getName() : '',
                    'zip' => array_key_exists('postcode', $customer) ? $customer['postcode'] : '',
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
            $this->bxFiles->savePartToCsv('customers.csv', $data);
            $data = null;

            $count = count($customers_to_save);
            $page++;

        } while ($count >= $limit);

        $customers = null;
        if ($this->config->isCustomersExportEnabled($account)) {
            $customerSourceKey = $this->bxData->addMainCSVCustomerFile($this->bxFiles->getPath('customers.csv'), 'customer_id');
            foreach ($customer_attributes as $prop) {
                if($prop == 'id') {
                    continue;
                }

                $this->bxData->addSourceStringField($customerSourceKey, $prop, $prop);
            }

            $this->logger->info('bxLog: Customers - exporting additional tables for account: ' . $account);
            $this->exportExtraTables('customers', $this->config->getAccountExtraTablesByEntityType($account, 'customers'));
        }
        $this->logger->info('bxLog: Customers - end of exporting for account: ' . $account);
    }


    /**
     * @param $account
     * @return array
     */
    protected function getTransactionAttributes($account)
    {
        $this->logger->info('bxLog: get all transaction attributes for account: ' . $account);
        $dbConfig = $this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB);
        if(!isset($dbConfig['connection']['default']['dbname'])) {
            $this->logger->info("ConfigOptionsListConstants::CONFIG_PATH_DB doesn't provide a dbname in ['connection']['default']['dbname']");
            return [];
        }
        $attributes = $this->exporterResource->getTransactionColumnsAsAttributes();
        $this->logger->info('bxLog: get configured transaction attributes for account: ' . $account);
        $filteredAttributes = $this->config->getAccountTransactionsProperties($account, $attributes, []);

        $attributes = array_intersect($attributes, $filteredAttributes);
        $this->logger->info('bxLog: returning configured transaction attributes for account ' . $account . ': ' . implode(',', array_values($attributes)));

        return $attributes;
    }

    /**
     * @param $account
     * @return array
     */
    protected function getCustomerAttributes($account){

        $attributes = [];
        $this->logger->info('bxLog: get all customer attributes for account: ' . $account);

        $attributes = $this->exporterResource->getCustomerAttributes();
        $this->logger->info('bxLog: get configured customer attributes for account: ' . $account);
        $filteredAttributes = $this->config->getAccountCustomersProperties($account, $attributes, array('dob', 'gender'));

        $attributes = array_intersect($attributes, $filteredAttributes);
        $this->logger->info('bxLog: returning configured customer attributes for account ' . $account . ': ' . implode(',', array_values($attributes)));

        return $attributes;
    }

    /**
     * @param $account
     * @param $exportFull
     */
    protected function exportTransactions($account)
    {
        // don't export transactions in delta sync or when disabled
        if(!$this->config->isTransactionsExportEnabled($account)) {
            return;
        }

        $this->logger->info('bxLog: starting transaction export for account ' . $account);

        $limit = 5000;
        $page = 1;
        $header = true;
        $transactions_to_save = array();
        $date = date("Y-m-d H:i:s", strtotime("-1 month"));
        $transaction_attributes = $this->getTransactionAttributes($account);
        if (count($transaction_attributes)) {
            $billing_columns = $shipping_columns = array();
            foreach ($transaction_attributes as $attribute) {
                $billing_columns['billing_' . $attribute] = $attribute;
                $shipping_columns['shipping_' . $attribute] = $attribute;
            }
        }
        $tempSelect = $this->exporterResource->prepareTransactionsSelectByShippingBillingModeSql($account, $billing_columns, $shipping_columns, $this->config->getTransactionMode($account));
        while (true) {
            $this->logger->info('bxLog: Transactions - load page ' . $page . ' for account ' . $account);
            $configurable = array();
            $transactions = $this->exporterResource->getTransactionsByLimitPage($limit, $page, $tempSelect);
            if(sizeof($transactions) < 1 && $page == 1){
                return;
            } elseif (sizeof($transactions) < 1 && $page > 1) {
                break;
            }

            $this->logger->info('bxLog: Transactions - loaded page ' . $page . ' for account ' . $account);
            foreach ($transactions as $transaction) {
                //is configurable
                if ($transaction['product_type'] == 'configurable') {
                    $configurable[$transaction['product_id']] = $transaction;
                }

                $productOptions = @unserialize($transaction['product_options']);
                if($productOptions === FALSE) {
                    $productOptions = @json_decode($transaction['product_options'], true);
                    if(is_null($productOptions)) {
                        $this->logger->error("bxLog: failed to unserialize and json decode product_options for order with entity_id: " . $transaction['entity_id']);
                        continue;
                    }
                }

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
                    'shipping_method'=> $transaction['shipping_method'],
                    'payment_method' => $transaction['payment_method'],
                    'payment_name' => $this->getMethodTitleFromAdditionalInformationJson($transaction['payment_title'])
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
            $data = $transactions_to_save;
            $transactions_to_save = null;
            $configurable = null;
            $transactions = null;

            if ($header) {
                if(count($data) < 1) {
                    return;
                }
                $data = array_merge(array(array_keys(end($data))), $data);
                $header = false;
            }

            $this->logger->info('bxLog: Transactions - save to file for account ' . $account);
            $this->bxFiles->savePartToCsv('transactions.csv', $data);
            $data = null;
            $page++;
        }

        $sourceKey = $this->bxData->setCSVTransactionFile($this->bxFiles->getPath('transactions.csv'), 'order_id', 'entity_id', 'customer_id', 'order_date', 'total_order_value', 'price', 'discounted_price');
        $this->bxData->addSourceCustomerGuestProperty($sourceKey,'guest_id');

        $this->logger->info('bxLog: Transactions - exporting additional tables for account: ' . $account);
        $this->exportExtraTables('transactions', $this->config->getAccountExtraTablesByEntityType($account,'transactions'));

        $this->logger->info('bxLog: Transactions - end of export for account ' . $account);
    }

    /**
     * Reading payment method name from payment additional information
     *
     * @param $additionalInformation
     * @return string
     */
    protected function getMethodTitleFromAdditionalInformationJson($additionalInformation)
    {
        $additionalInformation = json_decode($additionalInformation, true);
        if(isset($additionalInformation['method_title']))
        {
            return $additionalInformation['method_title'];
        }

        return '';
    }

    /**
     * @param $account
     * @param $files
     * @return bool
     */
    protected function exportProducts($account){

        $languages = $this->config->getAccountLanguages($account);
        $this->logger->info('bxLog: Products - start of export for account ' . $account);

        $attrs = $this->getStoreProductAttributes($account);
        $this->logger->info('bxLog: Products - get info about attributes - before for account ' . $account);

        $countMax = 1000000; //$this->_storeConfig['maximum_population'];
        $limit = 1000; //$this->_storeConfig['export_chunk'];
        $totalCount = 0;
        $page = 1;
        $header = true;
        $duplicateIds = $this->getDuplicateIds($account, $languages);

        while (true) {
            if ($countMax > 0 && $totalCount >= $countMax) {
                break;
            }

            $data = [];
            $fetchedResult = $this->exporterResource->getProductEntityByLimitPageIndexerDelta($limit, $page, $this->getIndexerType(), $this->getDeltaIds());
            if(sizeof($fetchedResult)){
                foreach ($fetchedResult as $r) {
                    if($r['group_id'] == null) $r['group_id'] = $r['entity_id'];
                    $data[] = $r;
                    $totalCount++;
                    if(isset($duplicateIds[$r['entity_id']])){
                        $r['group_id'] = $r['entity_id'];
                        $r['entity_id'] = 'duplicate' . $r['entity_id'];
                        $data[] = $r;
                    }
                }
            }else{
                if($totalCount == 0){
                    return false;
                }
                break;
            }

            if ($header && count($data) > 0) {
                $data = array_merge(array(array_keys(end($data))), $data);
                $header = false;
            }

            $this->bxFiles->savePartToCsv('products.csv', $data);
            $data = null;
            $page++;
        }
        $attributeSourceKey = $this->bxData->addMainCSVItemFile($this->bxFiles->getPath('products.csv'), 'entity_id');
        $this->bxData->addSourceStringField($attributeSourceKey, 'group_id', 'group_id');
        $this->bxData->addFieldParameter($attributeSourceKey, 'group_id', 'multiValued', 'false');

        $productAttributes = $this->exporterResource->getProductAttributesByCodes($attrs);
        $this->logger->info('bxLog: Products - connected to DB, built attribute info query for account ' . $account);

        $attrsFromDb = ['int'=>[], 'varchar'=>[], 'text'=>[], 'decimal'=>[], 'datetime'=>[]];
        foreach ($productAttributes as $r) {
            $type = $r['backend_type'];
            if (isset($attrsFromDb[$type])) {
                $attrsFromDb[$type][$r['attribute_id']] =[
                    'attribute_code' => $r['attribute_code'],
                    'is_global' => $r['is_global'],
                    'frontend_input' => $r['frontend_input']
                ];
            }
        }

        $this->exportProductAttributes($attrsFromDb, $languages, $account, $attributeSourceKey, $duplicateIds);
        $this->exportProductInformation($duplicateIds, $account, $languages);

        $this->logger->info('bxLog: Products - exporting additional tables for account: ' . $account);
        $this->exportExtraTables('products', $this->config->getAccountExtraTablesByEntityType($account,'products'));

        return true;
    }

    /**
     * @param array $attrs
     * @param $languages
     * @param $account
     * @param $mainSourceKey
     * @param $duplicateIds
     * @throws \Exception
     */
    protected function exportProductAttributes($attrs = array(), $languages, $account, $mainSourceKey, $duplicateIds)
    {
        $this->logger->info('bxLog: Products - exportProductAttributes for account ' . $account);
        $paramPriceLabel = '';
        $paramSpecialPriceLabel = '';

        $db = $this->rs->getConnection();
        $columns = array(
            'entity_id',
            'attribute_id',
            'value',
            'store_id'
        );
        $this->bxFiles->prepareProductFiles($attrs);
        foreach($attrs as $attrKey => $types)
        {
            foreach ($types as $typeKey => $type)
            {
                $optionSelect = in_array($type['frontend_input'], ['multiselect','select']);
                $data = [];
                $additionalData = [];
                $exportAttribute = false;
                $global = false;
                $getValueForDuplicate = false;
                $d = [];
                $headerLangRow = [];
                $optionValues = [];

                foreach ($languages as $langIndex => $lang)
                {
                    $select = $db->select()->from(
                        array('t_d' => $this->rs->getTableName('catalog_product_entity_' . $attrKey)),
                        $columns
                    );
                    if($this->getIndexerType() == 'delta') $select->where('t_d.entity_id IN(?)', $this->getDeltaIds());

                    $labelColumns[$lang] = 'value_' . $lang;
                    $storeObject = $this->config->getStore($account, $lang);
                    $storeId = $storeObject->getId();

                    $storeBaseUrl = $storeObject->getBaseUrl();
                    $imageBaseUrl = $storeObject->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . "catalog/product";
                    $storeObject = null;

                    if ($type['attribute_code'] == 'price'|| $type['attribute_code'] == 'special_price') {
                        if($langIndex == 0) {
                            $priceData = $this->exporterResource->getPriceByTypeAndIndexerType($attrKey, $typeKey, $this->getIndexerType(), $this->getDeltaIds());
                            if (sizeof($priceData)) {
                                $priceData = array_merge(array(array_keys(end($priceData))), $priceData);
                            } else {
                                $priceData = array(array('parent_id', 'value'));
                            }
                            $this->bxFiles->savePartToCsv($type['attribute_code'] . '.csv', $priceData);
                        }
                    }

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
                            if($this->getIndexerType() == 'delta')$select1->where('t_g.entity_id IN(?)', $this->getDeltaIds());
                            foreach ($db->fetchAll($select1) as $r) {
                                $data[] = $r;
                            }
                            continue;
                        }
                    }

                    if($optionSelect){
                        $fetchedOptionValues = $this->exporterResource->getProductOptionValuesByStoreAndKey($storeId, $typeKey);
                        if($fetchedOptionValues){
                            foreach($fetchedOptionValues as $v){
                                if(isset($optionValues[$v['option_id']])){
                                    $optionValues[$v['option_id']]['value_' . $lang] = $v['value'];
                                }else{
                                    $optionValues[$v['option_id']] = array($type['attribute_code'] . '_id' => $v['option_id'],
                                        'value_' . $lang => $v['value']);
                                }
                            }
                        }else{
                            $exportAttribute = true;
                            $optionSelect = false;
                        }
                        $fetchedOptionValues = null;
                    }
                    $select->where('t_d.attribute_id = ?', $typeKey)->where('t_d.store_id = 0 OR t_d.store_id = ?',$storeId);

                    if ($type['attribute_code'] == 'visibility') {
                        $getValueForDuplicate = true;
                        $select = $this->exporterResource->getProductAttributeParentUnionSqlByIndexerDelta($type['attribute_code'], $attrKey, $storeId, $this->getIndexerType(), $this->getDeltaIds());
                    }

                    if ($type['attribute_code'] == 'status') {
                        $getValueForDuplicate = true;
                        $select = $this->exporterResource->getProductStatusParentDependabilityByIndexerDelta($storeId, $this->getIndexerType(), $this->getDeltaIds());
                    }

                    $fetchedResult = $db->fetchAll($select);
                    if (sizeof($fetchedResult)) {

                        foreach ($fetchedResult as $i => $row) {

                            if (isset($data[$row['entity_id']]) && !$optionSelect) {

                                if(isset($data[$row['entity_id']]['value_' . $lang])){
                                    if($row['store_id'] > 0){
                                        $data[$row['entity_id']]['value_' . $lang] = $row['value'];
                                        if(isset($duplicateIds[$row['entity_id']])){
                                            $data['duplicate'.$row['entity_id']]['value_' . $lang] = $getValueForDuplicate ?
                                                $this->exporterResource->getProductAttributeValue($row['entity_id'], $typeKey, $storeId) :
                                                $row['value'];
                                        }
                                        if(isset($additionalData[$row['entity_id']])){
                                            if ($type['attribute_code'] == 'url_key') {
                                                $url = $storeBaseUrl . $row['value'] . '.html';
                                            } else {
                                                $url = $imageBaseUrl . $row['value'];
                                            }
                                            $additionalData[$row['entity_id']]['value_' . $lang] = $url;
                                            if(isset($duplicateIds[$row['entity_id']])){
                                                $additionalData['duplicate'.$row['entity_id']]['value_' . $lang] = $url;
                                            }
                                        }
                                    }
                                }else{
                                    $data[$row['entity_id']]['value_' . $lang] = $row['value'];
                                    if(isset($duplicateIds[$row['entity_id']])){
                                        $data['duplicate'.$row['entity_id']]['value_' . $lang] = $getValueForDuplicate ?
                                            $this->exporterResource->getProductAttributeValue($row['entity_id'], $typeKey, $storeId) :
                                            $row['value'];
                                    }
                                    if (isset($additionalData[$row['entity_id']])) {
                                        if ($type['attribute_code'] == 'url_key') {
                                            $url = $storeBaseUrl . $row['value'] . '.html';

                                        } else {
                                            $url = $imageBaseUrl . $row['value'];
                                        }
                                        $additionalData[$row['entity_id']]['value_' . $lang] = $url;
                                        if(isset($duplicateIds[$row['entity_id']])){
                                            $additionalData['duplicate'.$row['entity_id']]['value_' . $lang] = $url;
                                        }
                                    }
                                }
                                continue;
                            } else {
                                if ($type['attribute_code'] == 'url_key') {
                                    if ($this->config->exportProductUrl($account)) {
                                        $url = $storeBaseUrl . $row['value'] . '.html';
                                        $additionalData[$row['entity_id']] = array('entity_id' => $row['entity_id'],
                                            'store_id' => $row['store_id'],
                                            'value_' . $lang => $url);
                                        if(isset($duplicateIds[$row['entity_id']])){
                                            $additionalData['duplicate'.$row['entity_id']] = array(
                                                'entity_id' => 'duplicate'.$row['entity_id'],
                                                'value_' . $lang => $url);
                                        }
                                    }
                                }
                                if ($type['attribute_code'] == 'image') {
                                    if ($this->config->exportProductImages($account)) {
                                        $url = $imageBaseUrl . $row['value'];
                                        $additionalData[$row['entity_id']] = array('entity_id' => $row['entity_id'],
                                            'value_' . $lang => $url);
                                        if(isset($duplicateIds[$row['entity_id']])){
                                            $additionalData['duplicate'.$row['entity_id']] = array(
                                                'entity_id' => 'duplicate'.$row['entity_id'],
                                                'value_' . $lang => $url);
                                        }
                                    }
                                }
                                if ($type['is_global'] != 1){
                                    if($optionSelect){
                                        $values = explode(',',$row['value']);
                                        foreach($values as $v){
                                            $data[] = array('entity_id' => $row['entity_id'],
                                                $type['attribute_code'] . '_id' => $v);
                                            if(isset($duplicateIds[$row['entity_id']])){
                                                $data[] = array('entity_id' => 'duplicate'.$row['entity_id'],
                                                    $type['attribute_code'] . '_id' => $v);
                                            }
                                        }
                                    }else{
                                        if(!isset($data[$row['entity_id']])) {
                                            $data[$row['entity_id']] = array('entity_id' => $row['entity_id'],
                                                'store_id' => $row['store_id'],'value_' . $lang => $row['value']);
                                            if(isset($duplicateIds[$row['entity_id']])){
                                                $data['duplicate'.$row['entity_id']] = array(
                                                    'entity_id' => 'duplicate'.$row['entity_id'],
                                                    'store_id' => $row['store_id'],
                                                    'value_' . $lang => $getValueForDuplicate ?
                                                        $this->exporterResource->getProductAttributeValue($row['entity_id'], $typeKey, $storeId)
                                                        : $row['value']
                                                );
                                            }
                                        }
                                    }
                                    continue;
                                }else{

                                    if($optionSelect){
                                        $values = explode(',',$row['value']);
                                        foreach($values as $v){
                                            if(!isset($data[$row['entity_id'].$v])){
                                                $data[$row['entity_id'].$v] = array('entity_id' => $row['entity_id'],
                                                    $type['attribute_code'] . '_id' => $v);
                                                if(isset($duplicateIds[$row['entity_id']])){
                                                    $data[] = array('entity_id' => 'duplicate'.$row['entity_id'],
                                                        $type['attribute_code'] . '_id' => $v);
                                                }
                                            }
                                        }
                                    }else{

                                        $valueLabel = $type['attribute_code'] == 'visibility' ||
                                        $type['attribute_code'] == 'status' ||
                                        $type['attribute_code'] == 'special_from_date' ||
                                        $type['attribute_code'] == 'special_to_date' ? 'value_' . $lang : 'value';
                                        $data[$row['entity_id']] = array('entity_id' => $row['entity_id'],
                                            'store_id' => $row['store_id'],
                                            $valueLabel => $row['value']);
                                        if(isset($duplicateIds[$row['entity_id']])){
                                            $data['duplicate'.$row['entity_id']] = array(
                                                'entity_id' => 'duplicate'.$row['entity_id'],
                                                'store_id' => $row['store_id'],
                                                $valueLabel => $getValueForDuplicate ?
                                                    $this->exporterResource->getProductAttributeValue($row['entity_id'], $typeKey, $storeId)
                                                    : $row['value']
                                            );
                                        }
                                    }
                                }
                            }
                        }
                        if($type['is_global'] == 1 && !$optionSelect){
                            $global = true;
                            if($type['attribute_code'] != 'visibility' && $type['attribute_code'] != 'status' && $type['attribute_code'] != 'special_from_date' && $type['attribute_code'] != 'special_to_date') {
                                break;
                            }
                        }
                    }
                }

                if($optionSelect || $exportAttribute){
                    $optionHeader = array_merge(array($type['attribute_code'] . '_id'),$labelColumns);
                    $a = array_merge(array($optionHeader), $optionValues);
                    $this->bxFiles->savepartToCsv( $type['attribute_code'].'.csv', $a);
                    $optionValues = null;
                    $a = null;
                    $optionSourceKey = $this->bxData->addResourceFile(
                        $this->bxFiles->getPath($type['attribute_code'] . '.csv'), $type['attribute_code'] . '_id',
                        $labelColumns);
                    if(sizeof($data) == 0){
                        $d = array(array('entity_id',$type['attribute_code'] . '_id'));
                        $this->bxFiles->savepartToCsv('product_' . $type['attribute_code'] . '.csv',$d);
                        $fieldId = $this->bxGeneral->sanitizeFieldName($type['attribute_code']);
                        $attributeSourceKey = $this->bxData->addCSVItemFile($this->bxFiles->getPath('product_' . $type['attribute_code'] . '.csv'), 'entity_id');
                        $this->bxData->addSourceLocalizedTextField($attributeSourceKey,$type['attribute_code'],
                            $type['attribute_code'] . '_id', $optionSourceKey);
                    }
                }

                if (sizeof($data)) {
                    if(!$global || $type['attribute_code'] == 'visibility' ||
                        $type['attribute_code'] == 'status' ||
                        $type['attribute_code'] == 'special_from_date' ||
                        $type['attribute_code'] == 'special_to_date'){
                        if(!$optionSelect){
                            $headerLangRow = array_merge(array('entity_id','store_id'), $labelColumns);
                            if(sizeof($additionalData)){
                                $additionalHeader = array_merge(array('entity_id','store_id'), $labelColumns);
                                $d = array_merge(array($additionalHeader), $additionalData);
                                if ($type['attribute_code'] == 'url_key') {
                                    $this->bxFiles->savepartToCsv('product_default_url.csv', $d);
                                    $sourceKey = $this->bxData->addCSVItemFile($this->bxFiles->getPath('product_default_url.csv'), 'entity_id');
                                    $this->bxData->addSourceLocalizedTextField($sourceKey, 'default_url', $labelColumns);
                                } else {
                                    $this->bxFiles->savepartToCsv('product_cache_image_url.csv', $d);
                                    $sourceKey = $this->bxData->addCSVItemFile($this->bxFiles->getPath('product_cache_image_url.csv'), 'entity_id');
                                    $this->bxData->addSourceLocalizedTextField($sourceKey, 'cache_image_url',$labelColumns);
                                }
                            }
                            $d = array_merge(array($headerLangRow), $data);
                        }else{
                            $d = array_merge(array(array('entity_id',$type['attribute_code'] . '_id')), $data);
                        }
                    }else {
                        $d = array_merge(array(array_keys(end($data))), $data);
                    }

                    $this->bxFiles->savepartToCsv('product_' . $type['attribute_code'] . '.csv', $d);
                    $fieldId = $this->bxGeneral->sanitizeFieldName($type['attribute_code']);
                    $attributeSourceKey = $this->bxData->addCSVItemFile($this->bxFiles->getPath('product_' . $type['attribute_code'] . '.csv'), 'entity_id');
                    switch($type['attribute_code']){
                        case $optionSelect == true:
                            $this->bxData->addSourceLocalizedTextField($attributeSourceKey,$type['attribute_code'],
                                $type['attribute_code'] . '_id', $optionSourceKey);
                            break;
                        case 'name':
                            $this->bxData->addSourceTitleField($attributeSourceKey, $labelColumns);
                            break;
                        case 'description':
                            $this->bxData->addSourceDescriptionField($attributeSourceKey, $labelColumns);
                            break;
                        case 'visibility':
                        case 'status':
                        case 'special_from_date':
                        case 'special_to_date':
                            $lc = array();
                            foreach ($languages as $lcl) {
                                $lc[$lcl] = 'value_' . $lcl;
                            }
                            $this->bxData->addSourceLocalizedTextField($attributeSourceKey, $fieldId, $lc);
                            break;
                        case 'price':
                            if(!$global){
                                $col = null;
                                foreach($labelColumns as $k => $v) {
                                    $col = $v;
                                    break;
                                }
                                $this->bxData->addSourceListPriceField($mainSourceKey, 'entity_id');
                            }else {
                                $this->bxData->addSourceListPriceField($mainSourceKey, 'entity_id');
                            }

                            if(!$global){
                                $this->bxData->addSourceLocalizedTextField($attributeSourceKey, "price_localized", $labelColumns);
                            } else {
                                $this->bxData->addSourceStringField($attributeSourceKey, "price_localized", 'value');
                            }

                            $paramPriceLabel = $global ? 'value' : reset($labelColumns);
                            $this->bxData->addFieldParameter($mainSourceKey,'bx_listprice', 'pc_fields', 'CASE WHEN (price.'.$paramPriceLabel.' IS NULL OR price.'.$paramPriceLabel.' <= 0) AND ref.value IS NOT NULL then ref.value ELSE price.'.$paramPriceLabel.' END as price_value');
                            $this->bxData->addFieldParameter($mainSourceKey,'bx_listprice', 'pc_tables', 'LEFT JOIN `%%EXTRACT_PROCESS_TABLE_BASE%%_products_product_price` as price ON t.entity_id = price.entity_id, LEFT JOIN `%%EXTRACT_PROCESS_TABLE_BASE%%_products_resource_price` as ref ON t.entity_id = ref.parent_id');

                            $this->bxData->addResourceFile(
                                $this->bxFiles->getPath($type['attribute_code'] . '.csv'), 'parent_id','value'
                            );
                            break;
                        case 'special_price':
                            if(!$global){
                                $col = null;
                                foreach($labelColumns as $k => $v) {
                                    $col = $v;
                                    break;
                                }
                                $this->bxData->addSourceDiscountedPriceField($mainSourceKey, 'entity_id');
                            }else {
                                $this->bxData->addSourceDiscountedPriceField($mainSourceKey, 'entity_id');
                            }
                            if(!$global){
                                $this->bxData->addSourceLocalizedTextField($attributeSourceKey, "special_price_localized", $labelColumns);
                            } else {
                                $this->bxData->addSourceStringField($attributeSourceKey, "special_price_localized", 'value');
                            }

                            $paramSpecialPriceLabel = $global ? 'value' : reset($labelColumns);
                            $this->bxData->addFieldParameter($mainSourceKey,'bx_discountedprice', 'pc_fields', 'CASE WHEN (price.'.$paramSpecialPriceLabel.' IS NULL OR price.'.$paramSpecialPriceLabel.' <= 0) AND ref.value IS NOT NULL then ref.value ELSE price.'.$paramSpecialPriceLabel.' END as price_value');
                            $this->bxData->addFieldParameter($mainSourceKey,'bx_discountedprice', 'pc_tables', 'LEFT JOIN `%%EXTRACT_PROCESS_TABLE_BASE%%_products_product_special_price` as price ON t.entity_id = price.entity_id, LEFT JOIN `%%EXTRACT_PROCESS_TABLE_BASE%%_products_resource_special_price` as ref ON t.entity_id = ref.parent_id');

                            $this->bxData->addResourceFile(
                                $this->bxFiles->getPath($type['attribute_code'] . '.csv'), 'parent_id','value'
                            );
                            break;
                        case ($attrKey == ('int' || 'decimal')) && $type['is_global'] == 1:
                            $this->bxData->addSourceNumberField($attributeSourceKey, $fieldId, 'value');
                            break;
                        default:
                            if(!$global){
                                $this->bxData->addSourceLocalizedTextField($attributeSourceKey, $fieldId, $labelColumns);
                            }else {
                                $this->bxData->addSourceStringField($attributeSourceKey, $fieldId, 'value');
                            }
                            break;
                    }
                }
                $data = null;
                $additionalData = null;
                $d = null;
                $labelColumns = null;
            }

        }
        $this->bxData->addSourceNumberField($mainSourceKey, 'bx_grouped_price', 'entity_id');
        $this->bxData->addFieldParameter($mainSourceKey,'bx_grouped_price', 'pc_fields', 'CASE WHEN sref.value IS NOT NULL AND sref.value > 0 AND (ref.value IS NULL OR sref.value < ref.value) THEN sref.value WHEN ref.value IS NOT NULL then ref.value WHEN sprice.'.$paramSpecialPriceLabel.' IS NOT NULL AND sprice.'.$paramSpecialPriceLabel.' > 0 AND price.'.$paramPriceLabel.' > sprice.'.$paramSpecialPriceLabel.' THEN sprice.'.$paramSpecialPriceLabel.' ELSE price.'.$paramPriceLabel.' END as price_value');
        $this->bxData->addFieldParameter($mainSourceKey,'bx_grouped_price', 'pc_tables', 'LEFT JOIN `%%EXTRACT_PROCESS_TABLE_BASE%%_products_product_price` as price ON t.entity_id = price.entity_id, LEFT JOIN `%%EXTRACT_PROCESS_TABLE_BASE%%_products_resource_price` as ref ON t.group_id = ref.parent_id, LEFT JOIN `%%EXTRACT_PROCESS_TABLE_BASE%%_products_product_special_price` as sprice ON t.entity_id = sprice.entity_id, LEFT JOIN `%%EXTRACT_PROCESS_TABLE_BASE%%_products_resource_special_price` as sref ON t.group_id = sref.parent_id');
        $this->bxData->addFieldParameter($mainSourceKey,'bx_grouped_price', 'multiValued', 'false');

        $this->bxFiles->clearEmptyFiles("product_");
    }

    /**
     * @param $duplicateIds
     * @param $account
     * @param $languages
     * @throws \Exception
     */
    protected function exportProductInformation($duplicateIds, $account, $languages)
    {
        $this->logger->info('bxLog: Products - exportProductInformation for account ' . $account);
        $fetchedResult = array();
        $db = $this->rs->getConnection();
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
        if($this->getIndexerType() == 'delta') $select->where('product_id IN(?)', $this->getDeltaIds());

        $fetchedResult = $db->fetchAll($select);
        if(sizeof($fetchedResult)){
            foreach ($fetchedResult as $r) {
                $data[] = array('entity_id'=>$r['entity_id'], 'qty'=>$r['qty']);
                if(isset($duplicateIds[$r['entity_id']])){
                    $data[] = array('entity_id'=>'duplicate'.$r['entity_id'], 'qty'=>$r['qty']);
                }
            }
            $d = array_merge(array(array_keys(end($data))), $data);
            $this->bxFiles->savePartToCsv('product_stock.csv', $d);
            $data = null;
            $d = null;
            $attributeSourceKey = $this->bxData->addCSVItemFile($this->bxFiles->getPath('product_stock.csv'), 'entity_id');
            $this->bxData->addSourceNumberField($attributeSourceKey, 'qty', 'qty');
        }
        $fetchedResult = null;

        //product website
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
        if($this->getIndexerType() == 'delta') $select->where('product_id IN(?)', $this->getDeltaIds());

        $fetchedResult = $db->fetchAll($select);
        if(sizeof($fetchedResult)){
            foreach ($fetchedResult as $r) {
                $data[] = $r;
                if(isset($duplicateIds[$r['entity_id']])){
                    $r['entity_id'] = 'duplicate'.$r['entity_id'];
                    $data[] = $r;
                }
            }
            $d = array_merge(array(array_keys(end($data))), $data);
            $this->bxFiles->savePartToCsv('product_website.csv', $d);
            $data = null;
            $d = null;
            $attributeSourceKey = $this->bxData->addCSVItemFile($this->bxFiles->getPath('product_website.csv'), 'entity_id');
            $this->bxData->addSourceStringField($attributeSourceKey, 'website_name', 'name');
            $this->bxData->addSourceStringField($attributeSourceKey, 'website_id', 'website_id');
        }
        $fetchedResult = null;

        //product parent categories
        $select1 = $db->select()
            ->from(
                array('c_p_e' => $this->rs->getTableName('catalog_product_entity')),
                array('c_p_e.entity_id',)
            )
            ->joinLeft(
                array('c_p_r' => $this->rs->getTableName('catalog_product_relation')),
                'c_p_e.entity_id = c_p_r.child_id',
                array()
            );
        if($this->getIndexerType() == 'delta') $select1->where('product_id IN(?)', $this->getDeltaIds());

        $select2 = clone $select1;
        $select2->join(array('c_c_p' => $this->rs->getTableName('catalog_category_product')),
            'c_c_p.product_id = c_p_r.parent_id',
            array(
                'category_id'
            )
        );
        $select1->join(array('c_c_p' => $this->rs->getTableName('catalog_category_product')),
            'c_c_p.product_id = c_p_e.entity_id AND c_p_r.parent_id IS NULL',
            array(
                'category_id'
            )
        );
        $select = $db->select()->union(
            array($select1, $select2),
            \Zend_Db_Select::SQL_UNION
        );
        $fetchedResult = $db->fetchAll($select);

        if(sizeof($fetchedResult)) {
            foreach ($fetchedResult as $r) {
                $data[] = $r;
            }
            $fetchedResult = null;

            $select = $db->select()
                ->from(
                    array('c_p_e' => $this->rs->getTableName('catalog_product_entity')),
                    array('entity_id')
                )->join(
                    array('c_c_p' => $this->rs->getTableName('catalog_category_product')),
                    'c_c_p.product_id = c_p_e.entity_id',
                    array(
                        'category_id'
                    )
                )->where('c_p_e.entity_id IN(?)', $duplicateIds);

            $duplicateResult = $db->fetchAll($select);
            foreach ($duplicateResult as $r){
                $r['entity_id'] = 'duplicate'.$r['entity_id'];
                $data[] = $r;
            }
            $duplicateResult = null;
            $d = array_merge(array(array_keys(end($data))), $data);
            $this->bxFiles->savePartToCsv('product_categories.csv', $d);
            $data = null;
            $d = null;
        }
        $fetchedResult = null;

        //product super link
        $select = $db->select()
            ->from(
                $this->rs->getTableName('catalog_product_super_link'),
                array(
                    'entity_id' => 'product_id',
                    'parent_id',
                    'link_id'
                )
            );
        if($this->getIndexerType() == 'delta') $select->where('product_id IN(?)', $this->getDeltaIds());

        $fetchedResult = $db->fetchAll($select);
        if(sizeof($fetchedResult)) {
            foreach ($fetchedResult as $r) {
                $data[] = $r;
                if(isset($duplicateIds[$r['entity_id']])){
                    $r['entity_id'] = 'duplicate'.$r['entity_id'];
                    $data[] = $r;
                }
            }

            $d = array_merge(array(array_keys(end($data))), $data);
            $this->bxFiles->savePartToCsv('product_parent.csv', $d);
            $data = null;
            $d = null;
            $attributeSourceKey = $this->bxData->addCSVItemFile($this->bxFiles->getPath('product_parent.csv'), 'entity_id');
            $this->bxData->addSourceStringField($attributeSourceKey, 'parent_id', 'parent_id');
            $this->bxData->addSourceStringField($attributeSourceKey, 'link_id', 'link_id');
        }
        $fetchedResult = null;

        //product link
        $select = $db->select()
            ->from(
                array('pl'=> $this->rs->getTableName('catalog_product_link')),
                array(
                    'entity_id' => 'product_id',
                    'linked_product_id',
                    'lt.code'
                )
            )
            ->joinLeft(
                array('lt' => $this->rs->getTableName('catalog_product_link_type')),
                'pl.link_type_id = lt.link_type_id', array()
            )
            ->where('lt.link_type_id = pl.link_type_id');
        if($this->getIndexerType() == 'delta') $select->where('product_id IN(?)', $this->getDeltaIds());

        $fetchedResult = $db->fetchAll($select);
        if(sizeof($fetchedResult)) {
            foreach ($fetchedResult as $r) {
                $data[] = $r;
                if(isset($duplicateIds[$r['entity_id']])){
                    $r['entity_id'] = 'duplicate'.$r['entity_id'];
                    $data[] = $r;
                }
            }
            $d = array_merge(array(array_keys(end($data))), $data);
            $this->bxFiles->savePartToCsv('product_links.csv', $d);
            $data = null;
            $d = null;
            $attributeSourceKey = $this->bxData->addCSVItemFile($this->bxFiles->getPath('product_links.csv'), 'entity_id');
            $this->bxData->addSourceStringField($attributeSourceKey, 'code', 'code');
            $this->bxData->addSourceStringField($attributeSourceKey, 'linked_product_id', 'linked_product_id');
        }
        $this->logger->info("exportProductInformation finished");
        $fetchedResult = null;

        //product parent title
        $attrId = $this->exporterResource->getAttributeIdByAttributeCodeAndEntityType('name', \Magento\Catalog\Setup\CategorySetup::CATALOG_PRODUCT_ENTITY_TYPE_ID);
        $lvh = array();
        foreach ($languages as $language) {
            $lvh[$language] = 'value_'.$language;
            $store = $this->config->getStore($account, $language);
            $storeId = $store->getId();
            $store = null;

            $select1 = $db->select()
                ->from(
                    array('c_p_e' => $this->rs->getTableName('catalog_product_entity')),
                    array('entity_id')
                )
                ->joinLeft(
                    array('c_p_r' => $this->rs->getTableName('catalog_product_relation')),
                    'c_p_e.entity_id = c_p_r.child_id',
                    array('parent_id')
                );

            $select1->where('t_d.attribute_id = ?', $attrId)->where('t_d.store_id = 0 OR t_d.store_id = ?',$storeId);
            if($this->getIndexerType() == 'delta') $select1->where('c_p_e.entity_id IN(?)', $this->getDeltaIds());

            $select2 = clone $select1;
            $select2->join(
                array('t_d' => $this->rs->getTableName('catalog_product_entity_varchar')),
                't_d.entity_id = c_p_e.entity_id AND c_p_r.parent_id IS NULL',
                array(
                    't_d.value',
                    't_d.store_id'
                )
            );
            $select1->join(
                array('t_d' => $this->rs->getTableName('catalog_product_entity_varchar')),
                't_d.entity_id = c_p_r.parent_id',
                array(
                    't_d.value',
                    't_d.store_id'
                )
            );
            $select = $db->select()->union(
                array($select1, $select2),
                \Zend_Db_Select::SQL_UNION
            );
            $fetchedResult = $db->fetchAll($select);

            if (sizeof($fetchedResult)) {
                foreach ($fetchedResult as $r) {
                    if (isset($data[$r['entity_id']])) {
                        if(isset($data[$r['entity_id']]['value_' . $language])){
                            if($r['store_id'] > 0){
                                $data[$r['entity_id']]['value_' . $language] = $r['value'];
                            }
                        }else{
                            $data[$r['entity_id']]['value_' . $language] = $r['value'];
                        }
                        continue;
                    }
                    $data[$r['entity_id']] = array('entity_id' => $r['entity_id'], 'value_' . $language => $r['value']);
                }

                $fetchedResult = null;
                $select = $db->select()
                    ->from(
                        array('c_p_e' => $this->rs->getTableName('catalog_product_entity')),
                        array('entity_id', new \Zend_Db_Expr("CASE WHEN c_p_e_v_b.value IS NULL THEN LOWER(c_p_e_v_a.value) ELSE LOWER(c_p_e_v_b.value) END as value"))
                    )->joinLeft(
                        array('c_p_e_v_a' => $this->rs->getTableName('catalog_product_entity_varchar')),
                        '(c_p_e_v_a.attribute_id = ' . $attrId . ' AND c_p_e_v_a.store_id = 0) AND (c_p_e_v_a.entity_id = c_p_e.entity_id)',
                        array()
                    )->joinLeft(
                        array('c_p_e_v_b' => $this->rs->getTableName('catalog_product_entity_varchar')),
                        '(c_p_e_v_b.attribute_id = ' . $attrId . ' AND c_p_e_v_b.store_id = ' . $storeId . ') AND (c_p_e_v_b.entity_id = c_p_e.entity_id)',
                        array()
                    )->where('c_p_e.entity_id IN (?)', $duplicateIds);

                $duplicateResult = $db->fetchAll($select);
                foreach ($duplicateResult as $r){
                    $r['entity_id'] = 'duplicate'.$r['entity_id'];
                    if (isset($data[$r['entity_id']])) {
                        $data[$r['entity_id']]['value_' . $language] = $r['value'];
                        continue;
                    }
                    $data[$r['entity_id']] = array('entity_id' => $r['entity_id'], 'value_' . $language => $r['value']);
                }
                $duplicateResult = null;

            }
        }
        $data = array_merge(array(array_keys(end($data))), $data);
        $this->bxFiles->savePartToCsv('product_bx_parent_title.csv', $data);
        $attributeSourceKey = $this->bxData->addCSVItemFile($this->bxFiles->getPath('product_bx_parent_title.csv'), 'entity_id');
        $this->bxData->addSourceLocalizedTextField($attributeSourceKey, 'bx_parent_title', $lvh);
        $this->bxData->addFieldParameter($attributeSourceKey,'bx_parent_title', 'multiValued', 'false');
    }

    /**
     * @param $account
     * @param $languages
     * @return array
     * @throws \Exception
     */
    protected function getDuplicateIds($account, $languages){
        $ids = [];
        $attributeId = $this->exporterResource->getAttributeIdByAttributeCodeAndEntityType('visibility', \Magento\Catalog\Setup\CategorySetup::CATALOG_PRODUCT_ENTITY_TYPE_ID);
        foreach ($languages as $language){
            $storeObject = $this->config->getStore($account, $language);
            $ids = $this->exporterResource->getProductDuplicateIds($storeObject->getId(), $attributeId, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE, $this->getIndexerType(), $this->getDeltaIds());
            $storeObject = null;
        }
        return $ids;
    }

    /**
     * Exporting additional tables that are related to entities
     * No logic on the connection is defined
     * To be added in the ETL
     *
     * @param $entity
     * @param array $tables
     * @return $this
     */
    public function exportExtraTables($entity, $tables = [])
    {
        if(empty($tables))
        {
            $this->logger->info("bxLog: {$entity} no additional tables have been found.");
            return $this;
        }

        foreach($tables as $table)
        {
            try{
                $columns = $this->exporterResource->getColumnsByTableName($table);
                $tableContent = $this->exporterResource->getTableContent($table);
                $dataToSave = array_merge(array(array_keys(end($tableContent))), $tableContent);

                $fileName = "extra_". $table . ".csv";
                $this->bxFiles->savePartToCsv($fileName, $dataToSave);

                $this->bxData->addExtraTableToEntity($this->bxFiles->getPath($fileName), $entity, reset($columns), $columns);
                $this->logger->info("bxLog: {$entity} - additional table {$table} exported.");
            } catch (NoSuchEntityException $exception)
            {
                $this->logger->warning("bxLog: {$entity} additional table ". $exception->getMessage());
            } catch (\Exception $exception)
            {
                $this->logger->error("bxLog: {$entity} additional table error: ". $exception->getMessage());
            }
        }

        return $this;
    }
}
