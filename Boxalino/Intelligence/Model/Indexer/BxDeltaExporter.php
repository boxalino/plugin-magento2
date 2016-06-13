<?php
namespace Boxalino\Intelligence\Model\Indexer;

use Boxalino\Intelligence\Helper\BxIndexConfig;
use Boxalino\Intelligence\Helper\BxFiles;
use Boxalino\Intelligence\Helper\BxGeneral;

use Magento\Indexer\Model\Indexer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Catalog\Model\ProductFactory;

use \Psr\Log\LoggerInterface;
class BxDeltaExporter implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface{

    protected $config;
    protected $filesystem;
    protected $productHelper;
    protected $storeManager;
    protected $rs;
    protected $logger;
    protected $bxData;
    protected $bxGeneral;
    protected $_entityIds;
    protected $indexer;
    protected $deltaIds;
    protected $productMetaData;
    protected $mviewProcessor;
    /**
     * @param mixed $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }
    public function __construct(
        StoreManagerInterface $storeManager,
		Filesystem $filesystem,
        \Magento\Catalog\Helper\Product\Flat\Indexer $productHelper,
        \Magento\Framework\App\ResourceConnection $rs,
        LoggerInterface $logger,
        BxGeneral $bxGeneral,
        \Magento\Indexer\Model\Indexer $indexer,
        \Magento\Framework\App\ProductMetadata $productMetaData,
        \Magento\Framework\Mview\ProcessorInterface $mviewProcessor
    )
    {
        $this->indexer = $indexer->load('boxalino_indexer_delta');
        $this->storeManager = $storeManager;
        $this->filesystem = $filesystem;
        $this->productHelper = $productHelper;
        $this->rs = $rs;
        $this->logger = $logger;
        $this->bxGeneral = $bxGeneral;
        $this->productMetaData = $productMetaData;
        $this->mviewProcessor = $mviewProcessor;
        $libPath = __DIR__ . '/../../Lib';
        require_once($libPath . '/BxClient.php');
        \com\boxalino\bxclient\v1\BxClient::LOAD_CLASSES($libPath);
    }

    public function executeFull(){
        $this->exportStores($this->checkForDeltaIds());
    }
    public function executeList(array $ids){
        $this->exportStores($ids);
    }
    public function executeRow($id){
        $this->exportStores(array($id));
    }
    public function execute($ids){
        $this->exportStores($ids);
    }

    protected function exportStores($ids = array()){
        $this->deltaIds = $ids;
        $this->logger->info("bxLog: starting exportStores");
        $this->config = new BxIndexConfig($this->storeManager->getWebsites());
        $this->logger->info("bxLog: retrieved index config: " . $this->config->toString());

        foreach($this->config->getAccounts() as $account){
            $this->logger->info("bxLog: initialize files on account: " . $account);
            $files = new BxFiles($this->filesystem, $this->logger, $account, $this->config);

            $bxClient = new \com\boxalino\bxclient\v1\BxClient($account,$this->config->getAccountPassword($account),"");
            $this->bxData = new \com\boxalino\bxclient\v1\BxData($bxClient,
                $this->config->getAccountLanguages($account), $this->config->isAccountDev($account), true
            );

            $this->logger->info("bxLog: verify credentials for account: " . $account);
            $this->bxData->verifyCredentials();

            $this->logger->info('bxLog: Preparing the product attributes for each language of the account: ' . $account);
            $attributes = null;
            foreach ($this->config->getAccountLanguages($account) as $language) {
                $store = $this->config->getStore($account, $language);
                $this->logger->info('bxLog: Start getStoreProductAttributes for language . ' . $language . ' on store:' . $store->getId());
                $attributes = $this->getStoreProductAttributes($account, $store);
            }

            $this->logger->info('bxLog: Export product files for account: ' . $account);
            $exportProducts = $this->exportProducts($account, $files, $attributes);
            if(!$exportProducts){
                $this->logger->info('bxLog: No Products found for account: ' . $account);
                $this->logger->info('bxLog: Finished account: ' . $account);
            }else{
                $this->logger->info('bxLog: Push the Zip data file to the Data Indexing server for account: ' . $account);
                try{
                    $this->bxData->pushData();
//                $this->mviewProcessor->clearChangelog('boxalino_delta_index');
                }catch(\Exception $e){
                    throw $e;
                }
                $this->logger->info('bxLog: Finished account: ' . $account);
            }
        }
        $this->clearChangeLog();
    }

    protected function checkForDeltaIds(){
        $db = $this->rs->getConnection();
        $ids = array();
        if($db->isTableExists('boxalino_indexer_delta_cl') && $db->tableColumnExists('boxalino_indexer_delta_cl' , 'entity_id')){

            $select = $db->select()->from(
                array('changelog' => $this->rs->getTableName('boxalino_indexer_delta_cl')),
                'entity_id'
            )->group('entity_id');

            $fetchedResult = $db->fetchAll($select);
            if(sizeof($fetchedResult)){
                foreach($fetchedResult as $r){
                    if(!isset($r)){
                        continue;
                    }
                    $ids[] = $r['entity_id'];;
                }
            }
        }
        return $ids;
    }

    protected function clearChangeLog(){
        $db = $this->rs->getConnection();
        if($db->isTableExists('boxalino_indexer_delta_cl') && $db->tableColumnExists('boxalino_indexer_delta_cl' , 'entity_id')) {
            $db->truncateTable($db->getTableName('boxalino_indexer_delta_cl'));
        }
    }

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

    protected function exportProducts($account, $files, $attributes){
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

        $fetchedResult = $db->fetchAll($select);
        if($fetchedResult){
            foreach ($fetchedResult as $r) {
                $type = $r['backend_type'];
                if (isset($attrsFromDb[$type])) {
                    $attrsFromDb[$type][$r['attribute_id']] = array('attribute_code' => $r['attribute_code'], 'is_global' => $r['is_global']);
                }
            }
        }
        $fetchedResult = null;
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
                ->limit($limit, ($page - 1) * $limit)
                ->joinLeft(
                    array('p_t' => $this->rs->getTableName('catalog_product_super_link')),
                    'e.entity_id = p_t.product_id', array('group_id' => 'parent_id')
                )
                ->where('e.entity_id IN(?)', $this->deltaIds);

            $data = array();
            $fetchedResult = $db->fetchAll($select);
            if(sizeof($fetchedResult)){
                foreach ($fetchedResult as $r) {
                    if($r['group_id'] == null) $r['group_id'] = $r['entity_id'];
                    $data[$r['entity_id']] = $r;
                    $totalCount++;
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

            $files->savePartToCsv('products.csv', $data);
            $data = null;
            $page++;
        }
        $attributeSourceKey = $this->bxData->addMainCSVItemFile($files->getPath('products.csv'), 'entity_id');
        $this->bxData->addSourceStringField($attributeSourceKey, 'group_id', 'group_id');
        $this->exportProductAttributes($attrsFromDb, $languages, $account, $files);
        $this->exportProductInformation($files);
        return true;
    }

    protected function exportProductAttributes($attrs = array(), $languages, $account, $files){

        $db = $this->rs->getConnection();
        $columns = array(
            'entity_id',
            'value',
            'store_id'
        );
        $files->prepareProductFiles($attrs);

        foreach($attrs as $attrKey => $types){

            $select = $db->select()->from(
                array('t_d' => $this->rs->getTableName('catalog_product_entity_' . $attrKey)),
                $columns
            )->joinLeft(array('ea' => $this->rs->getTableName('eav_attribute')), 't_d.attribute_id = ea.attribute_id', array());
            $select->where('t_d.entity_id IN(?)', $this->deltaIds);

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
                            $select1->where('t_g.entity_id IN(?)', $this->deltaIds);

                            foreach ($db->fetchAll($select1) as $r) {
                                $data[] = $r;
                            }
                            continue;
                        }
                    }
                    $whereClause = clone $select;
                    $whereClause->where('t_d.attribute_id = ?', $typeKey)->where('t_d.store_id = ? OR t_d.store_id = 0', $storeId);
                    $fetchedResult = $db->fetchAll($whereClause);

                    if (sizeof($fetchedResult)) {

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
                                    $data[$row['entity_id']] = array('entity_id' => $row['entity_id'], 'value' => $row['value']);
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
                                $data[$row['entity_id']] = array('entity_id' => $row['entity_id'], 'value_' . $lang => $row['value']);
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
                            $this->bxData->addSourceListPriceField($attributeSourceKey, 'value');
                            break;
                        case 'special_price':
                            $this->bxData->addSourceDiscountedPriceField($attributeSourceKey, 'value');
                            break;
                        case ($attrKey == ('int' || 'decimal')) && $type['is_global'] > 0:
                            $this->bxData->addSourceNumberField($attributeSourceKey, $fieldId, 'value');
                            break;
                        case $attrKey == 'datetime':
                            $this->bxData->addSourceStringField($attributeSourceKey, $fieldId, 'value');
                            break;
                        default:
                            if(sizeof($labelColumns)){
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
            ->where('stock_id = ?', 1)->where('product_id IN(?)', $this->deltaIds);

        $fetchedResult = $db->fetchAll($select);
        if(sizeof($fetchedResult)){
            foreach ($fetchedResult as $r) {
                $data[] = array('entity_id'=>$r['entity_id'], 'qty'=>$r['qty']);
            }
            $d = array_merge(array(array_keys(end($data))), $data);
            $files->savePartToCsv('product_stock.csv', $d);
            $data = null;
            $d = null;
            $attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_stock.csv'), 'entity_id');
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
            )->where('product_id IN(?)', $this->deltaIds);

        $fetchedResult = $db->fetchAll($select);
        if(sizeof($fetchedResult)){
            foreach ($fetchedResult as $r) {
                $data[] = $r;
            }
            $d = array_merge(array(array_keys(end($data))), $data);
            $files->savePartToCsv('product_website.csv', $d);
            $data = null;
            $d = null;
            $attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_website.csv'), 'entity_id');
            $this->bxData->addSourceStringField($attributeSourceKey, 'website_name', 'name');
            $this->bxData->addSourceStringField($attributeSourceKey, 'website_id', 'website_id');
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
            )->where('product_id IN(?)', $this->deltaIds);

        $fetchedResult = $db->fetchAll($select);
        if(sizeof($fetchedResult)) {
            foreach ($fetchedResult as $r) {
                $data[] = $r;
            }

            $d = array_merge(array(array_keys(end($data))), $data);
            $files->savePartToCsv('product_parent.csv', $d);
            $data = null;
            $d = null;
            $attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_parent.csv'), 'entity_id');
            $this->bxData->addSourceStringField($attributeSourceKey, 'parent_id', 'parent_id');
            $this->bxData->addSourceStringField($attributeSourceKey, 'link_id', 'link_id');
        }
        $fetchedResult = null;

        //product categories
        $select = $db->select()
            ->from(
                $this->rs->getTableName('catalog_category_product'),
                array(
                    'entity_id' => 'product_id',
                    'category_id',
                    'position'
                )
            )->where('product_id IN(?)', $this->deltaIds);

        $fetchedResult = $db->fetchAll($select);
        if(sizeof($fetchedResult)) {
            foreach ($fetchedResult as $r) {
                $data[] = $r;
            }
            $d = array_merge(array(array_keys(end($data))), $data);
            $files->savePartToCsv('product_categories.csv', $d);
            $data = null;
            $d = null;
            $attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_categories.csv'), 'entity_id');
            $this->bxData->addSourceStringField($attributeSourceKey, 'category_id', 'category_id');
            $this->bxData->addSourceStringField($attributeSourceKey, 'position', 'position');
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
            ->where('lt.link_type_id = pl.link_type_id')->where('product_id IN(?)', $this->deltaIds);

        $fetchedResult = $db->fetchAll($select);
        if(sizeof($fetchedResult)) {
            foreach ($fetchedResult as $r) {
                $data[] = $r;
            }
            $d = array_merge(array(array_keys(end($data))), $data);
            $files->savePartToCsv('product_links.csv', $d);
            $data = null;
            $d = null;
            $attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_links.csv'), 'entity_id');
            $this->bxData->addSourceStringField($attributeSourceKey, 'code', 'code');
            $this->bxData->addSourceStringField($attributeSourceKey, 'linked_product_id', 'linked_product_id');
        }
    }
}