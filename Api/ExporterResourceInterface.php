<?php
namespace Boxalino\Intelligence\Api;

/**
 * Interface ExporterResourceInterface
 * Used by the Boxalino indexers to store db logic
 *
 * @package Boxalino\Intelligence\Api
 */
interface ExporterResourceInterface
{
    /**
     * G E N E R I C   F U N C T I O N A L I T Y
     */

    /**
     * @param string $table
     * @return mixed
     */
    public function getColumnsByTableName($table);

    /**
     * @param $code
     * @param $type
     * @return mixed
     */
    public function getAttributeIdByAttributeCodeAndEntityType($code, $type);

    /**
     * @param $table
     * @return mixed
     */
    public function getTableContent($table);


    /**
     * P R O D U C T   E X P O R T   F U N C T I O N A L I T Y
     */

    /**
     * @return mixed
     */
    public function getProductAttributes();

    /**
     * @param array $codes
     * @return mixed
     */
    public function getProductAttributesByCodes($codes = []);

    /**
     * @param $id
     * @param $attributeId
     * @param $storeId
     * @return mixed
     */
    public function getProductAttributeValue($id, $attributeId, $storeId);

    /**
     * @param $storeId
     * @param $attributeId
     * @param $condition
     * @return mixed
     */
    public function getProductDuplicateIds($storeId, $attributeId, $condition);

    /**
     * @param $limit
     * @param $page
     * @return mixed
     */
    public function getProductEntityByLimitPage($limit, $page);

    /**
     * @param $attributeCode
     * @param $type
     * @param $storeId
     * @return mixed
     */
    public function getProductAttributeParentUnionSqlByCodeTypeStore($attributeCode, $type, $storeId);

    /**
     * @param $storeId
     * @return mixed
     */
    public function getProductStatusParentDependabilityByStore($storeId);

    /**
     * @return mixed
     */
    public function getProductWebsiteInformation();

    /**
     * @return mixed
     */
    public function getProductSuperLinkInformation();

    /**
     * @return mixed
     */
    public function getProductLinksInformation();

    /**
     * @return mixed
     */
    public function getProductParentCategoriesInformation();

    /**
     * @param $storeId
     * @return mixed
     */
    public function getProductParentTitleInformationByStore($storeId);

    /**
     * @param $storeId
     * @param $attrId
     * @param array $duplicateIds
     * @return mixed
     */
    public function getProductParentTitleInformationByStoreAttrDuplicateIds($storeId, $attrId, $duplicateIds = []);

    /**
     * @param array $duplicateIds
     * @return mixed
     */
    public function getProductParentCategoriesInformationByDuplicateIds($duplicateIds = []);

    /**
     * @param $storeId
     * @return mixed
     */
    public function getCategoriesByStoreId($storeId);

    /**
     * @param $storeId
     * @param $key
     * @return mixed
     */
    public function getProductOptionValuesByStoreAndKey($storeId, $key);

    /**
     * @return mixed
     */
    public function getProductStockInformation();

    /**
     * @param $type
     * @param $key
     * @return mixed
     */
    public function getPriceByType($type, $key);

    /**
     * @param $type
     * @param $key
     * @return mixed
     */
    public function getPriceSqlByType($type, $key);

    /**
     * C U S T O M E R   E X P O R T   F U N C T I O N A L I T Y
     */

    /**
     * @return mixed
     */
    public function getCustomerAttributes();

    /**
     * @param array $codes
     * @return mixed
     */
    public function getCustomerAttributesByCodes($codes = []);

    /**
     * @param $limit
     * @param $page
     * @param array $attributeGroups
     * @return mixed
     */
    public function getCustomerAddressByFieldsAndLimit($limit, $page, $attributeGroups = []);

    /**
     * @param $attributes
     * @param $ids
     * @return mixed
     */
    public function getUnionCustomerAttributesByAttributesAndIds($attributes, $ids);


    /**
     * T R A N S A C T I O N  E X P O R T   F U N C T I O N A L I T Y
     */

    /**
     * @return mixed
     */
    public function getTransactionColumnsAsAttributes();

    /**
     * @param $account
     * @param array $billingColumns
     * @param array $shippingColumns
     * @param int $mode
     * @return mixed
     */
    public function prepareTransactionsSelectByShippingBillingModeSql($account, $billingColumns =[], $shippingColumns = [], $mode = 1);

    /**
     * @param $limit
     * @param $page
     * @param $initialSelect
     * @return mixed
     */
    public function getTransactionsByLimitPage($limit, $page, $initialSelect);

}