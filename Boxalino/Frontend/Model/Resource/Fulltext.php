<?php

require_once 'Mage/CatalogSearch/Model/Resource/Fulltext.php';

class Boxalino_Frontend_Model_Resource_Fulltext extends Mage_CatalogSearch_Model_Resource_Fulltext
{

    public function prepareResult($object, $queryText, $query)
    {

        $session = Mage::getSingleton('core/session');
        $session->unsetData('relax');
        $session->unsetData('relax_products');
        $session->unsetData('relax_products_extra');

        if (Mage::getStoreConfig('Boxalino_General/general/enabled') == 0) {
            return parent::prepareResult($object, $queryText, $query);
        }

        if($queryText == ''){
            return $this;
        }

        $searchAdapter = Mage::helper('Boxalino_Frontend')->getSearchAdapter();
        $entity_ids = $searchAdapter->getEntitiesIds();

        //prepare suggestion
        $relaxations = array();
        $searchRelaxation = $searchAdapter->getChoiceRelaxation();
        $suggestionConfig = Mage::getStoreConfig('Boxalino_General/search_suggestions');

        if (
            $suggestionConfig['enabled'] &&
            is_object($searchRelaxation) &&
            is_array($searchRelaxation->suggestionsResults) &&
            count($searchRelaxation->suggestionsResults) > 0 &&
            (
                count($entity_ids) <= $suggestionConfig['min'] ||
                count($entity_ids) >= $suggestionConfig['max']
            )
        ) {
            Boxalino_Frontend_Model_Logger::saveFrontActions('prepareResult', 'suggestions detected');

            foreach ($searchRelaxation->suggestionsResults as $suggestion) {
                $relaxations[] = array(
                    'hits' => $suggestion->totalHitCount,
                    'text' => $suggestion->queryText,
                    'href' => urlencode($suggestion->queryText)
                );
            }

            if ($suggestionConfig['sort']) {
                usort($relaxations, array($this, 'cmp'));
            }
        }

        $session->setData('relax', array_slice($relaxations, 0, $suggestionConfig['display']));
        Boxalino_Frontend_Model_Logger::saveFrontActions('prepareResult relax', $session->getData('relax'));


        $this->resetSearchResults($query);

        //relaxation
        $relaxations_extra = array();
        $relaxationConfig = Mage::getStoreConfig('Boxalino_General/search_relaxation');

        if (
            (
                $entity_ids === null ||
                count($entity_ids) <= $relaxationConfig['max']
            ) &&
            is_object($searchRelaxation) &&
            ( count($searchRelaxation->subphrasesResults) > 0) &&
            $relaxationConfig['enabled']
        ) {

            Boxalino_Frontend_Model_Logger::saveFrontActions('prepareResult', 'relaxations detected');

            //display current products
            $session = Mage::getSingleton('core/session');
            $session->setData('relax_products', $entity_ids);

            if (count($searchRelaxation->subphrasesResults) > 0) {
                if (count($relaxations) == 0) {
                    $relaxations_extra = array();
                }

                foreach ($searchRelaxation->subphrasesResults as $subphrase) {

                    if (count($relaxations_extra) >= $relaxationConfig['relaxations']) {
                        continue;
                    }

                    $relaxations_extra[$subphrase->queryText] = array();
                    foreach ($subphrase->hits as $hit) {
                        $relaxations_extra[$subphrase->queryText][] = $hit->values['id'][0];
                        if (count($relaxations_extra[$subphrase->queryText]) >= $relaxationConfig['products']) {
                            break;
                        }
                    }

                }

            }

            //display currently products
            $session->setData('relax_products_extra', $relaxations_extra);
            Boxalino_Frontend_Model_Logger::saveFrontActions('prepareResult relax_products_extra', $session->getData('relax_products_extra'));

            $this->resetSearchResults($query);

            return $this;

        } elseif (
            count($entity_ids) == 0 &&
            is_object($searchRelaxation) &&
            count($searchRelaxation->subphrasesResults) == 0 &&
            count($relaxations) > 0) {
            Boxalino_Frontend_Model_Logger::saveFrontActions('prepareResult', 'no relaxations');

            $q = $relaxations[0];
            $this->resetSearchResults($query);

            /**
             * Magento EE works peculiarly.
             * Magento EE loads facets before execute search one more time.
             * Magento CE works correctly.
             */
            try {
                if (Mage::getEdition() != 'Community') {

                    $params = $_GET;
                    $params['q'] = $q['text'];
                    $paramString = http_build_query($params);

                    $currentUrl = urldecode(Mage::helper('core/url')->getCurrentUrl());
                    $currentUrl = substr($currentUrl, 0, strpos($currentUrl, '?'));

                    header('Location: ' . $currentUrl . '?' . $paramString);
                    exit();
                }
            }catch (Exception $e){

            }


            Mage::helper('Boxalino_Frontend')->resetSearchAdapter();

            Mage::helper('catalogsearch')->setQueryText($q['text']);

            $searchAdapter = Mage::helper('Boxalino_Frontend')->getSearchAdapter();
            $entity_ids = $searchAdapter->getEntitiesIds();

            $session->unsetData('relax');
            $session->unsetData('relax_products');
            $session->unsetData('relax_products_extra');

        }

        return $this;

    }

    private function cmp($a, $b)
    {
        if ($a['hits'] == $b['hits']) {
            return 0;
        }
        return ($a['hits'] > $b['hits']) ? -1 : 1;
    }
}
