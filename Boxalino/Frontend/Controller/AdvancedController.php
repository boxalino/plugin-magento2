<?php
/**
 * Created by: Szymon Nosal <szymon.nosal@boxalino.com>
 * Created at: 06.06.14 11:36
 */

require_once "Mage/CatalogSearch/controllers/AdvancedController.php";

/**
 * Catalog Search Controller
 *
 * @category   Mage
 * @package    Mage_CatalogSearch
 * @module     Catalog
 */
class Boxalino_CemSearch_AdvancedController extends Mage_CatalogSearch_AdvancedController
{

    public function indexAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('catalogsearch/session');
        $this->renderLayout();
    }

    public function resultAction()
    {

        $queryAttribute = array();

        if (Mage::getStoreConfig('Boxalino_General/general/enabled') == 0) {
            return parent::resultAction();
        }

        $this->loadLayout();

        $params = $this->getRequest()->getQuery();

        $tmp = Mage::getModel('catalogsearch/advanced');
        try {
            $tmp->addFilters($params);
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('catalogsearch/session')->addError($e->getMessage());
            $this->_redirectError(
                Mage::getModel('core/url')
                    ->setQueryParams($params)
                    ->getUrl('*/*/')
            );
        }

        foreach ($tmp->getAttributes() as $at) {
            $queryAttribute[$at->getStoreLabel()] = $at->getAttributeCode();
        }

        $criteria = $tmp->getSearchCriterias();
        unset($tmp);
        $lang = substr(Mage::app()->getLocale()->getLocaleCode(), 0, 2);

        //setUp Boxalino
        $storeConfig = Mage::getStoreConfig('Boxalino_General/general');
        $generalConfig = Mage::getStoreConfig('Boxalino_General/search');

        $p13nConfig = new Boxalino_CemSearch_Helper_P13n_Config(
            $storeConfig['host'],
            Mage::helper('Boxalino_CemSearch')->getAccount(),
            $storeConfig['p13n_username'],
            $storeConfig['p13n_password'],
            $storeConfig['domain']
        );
        $p13nSort = new Boxalino_CemSearch_Helper_P13n_Sort();

        $p13n = new Boxalino_CemSearch_Helper_P13n_Adapter($p13nConfig);

        $limit = $generalConfig['advanced_search_limit'] == 0 ? 1000 : $generalConfig['advanced_search_limit'];

        //setup search
        $p13n->setupInquiry(
            $generalConfig['advanced_search'],
            $params['name'],
            $lang,
            array($generalConfig['entity_id'], 'discountedPrice', 'title_' . $lang, 'score'),
            $p13nSort,
            0, $limit
        );
        //add filters

        $skip = array('name');

        foreach ($params as $key => $value) {

            if (isset($value['from']) || isset($value['to'])) {
                $from = null;
                $to = null;

                if (isset($params[$key]['from']) && $params[$key]['from'] != '' /* && $params['price']['from'] >= 0*/) {
                    $from = $params[$key]['from'];
                }
                if (isset($params[$key]['to']) && $params[$key]['to'] != '' /*&& $params['price']['to'] >= 0*/) {
                    $to = $params[$key]['to'];
                }

                $skip[] = $key;

                if ($key == 'price') {
                    $key = 'discountedPrice';
                }

                if ($from == null && $to == null) {
                    continue;
                }
                $p13n->addFilterFromTo($key, $from, $to);

            }
        }

        foreach ($criteria as $criterium) {

            $name = Mage::helper("Boxalino_CemSearch")->sanitizeFieldName($queryAttribute[$criterium['name']]);

            if (in_array($name, $skip)) {
                continue;
            }

            $values = explode(", ", $criterium['value']);

            if ($name == 'description') {
                $name = 'body';
            } else {
                $name = 'products_' . $name;
            }

            $p13n->addFilter($name, $values, null);
        }

        //get result from boxalino
        $p13n->search();
        $p13n->prepareAdditionalDataFromP13n();
        $entity_ids = $p13n->getEntitiesIds();
        unset($p13n);

        try {
            Boxalino_CemSearch_Model_Logger::saveFrontActions('AdvancedController_ResultAction', 'storing catalogsearch/advanced for entities with id: ' . implode(', ', $entity_ids));
            Mage::getSingleton('catalogsearch/advanced')->addFilters($params, $entity_ids);
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('catalogsearch/session')->addError($e->getMessage());
            $this->_redirectError(
                Mage::getModel('core/url')
                    ->setQueryParams($this->getRequest()->getQuery())
                    ->getUrl('*/*/')
            );
        }

        $this->_initLayoutMessages('catalog/session');
        $this->renderLayout();

    }

}
