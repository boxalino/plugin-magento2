<?php
namespace Boxalino\CemSearch\Helper\P13n;

class Recommendation
{
    private $returnFields = array('id');
    private $results = array();

    public static function Instance()
    {
        static $inst = null;
        if ($inst === null) {
            $inst = new Boxalino_CemSearch_Helper_P13n_Recommendation();
        }
        return $inst;
    }

    public function getRecommendation($widgetType, $widgetName, $amount = 3)
    {
        if (empty($this->results)) {
            if ($widgetType == '') {
                $widgets = array(array(
                    'name' => $widgetName, 'min_recs' => 0, 'max_recs' => $amount
                ));
                $widgetType = 'product';
            } else {
                $widgets = $this->prepareWidgets($widgetType);
            }
            if (empty($widgets)) {
                return null;
            }
            $account = Mage::helper('Boxalino_CemSearch')->getAccount();
            $language = substr(Mage::app()->getLocale()->getLocaleCode(), 0, 2);
            $returnFields = $this->returnFields;

            $entity_id = Mage::getStoreConfig('Boxalino_General/search/entity_id');
            $authData['username'] = Mage::getStoreConfig('Boxalino_General/general/p13n_username');
            $authData['password'] = Mage::getStoreConfig('Boxalino_General/general/p13n_password');
            $entityIdFieldName = 'entity_id';
            if (isset($entity_id) && $entity_id !== '') {
                $entityIdFieldName = $entity_id;
            }

            $storeConfig = Mage::getStoreConfig('Boxalino_General/general');

            $p13nConfig = new Boxalino_CemSearch_Helper_P13n_Config(
                $storeConfig['host'],
                Mage::helper('Boxalino_CemSearch')->getAccount(),
                $storeConfig['p13n_username'],
                $storeConfig['p13n_password'],
                $storeConfig['domain']
            );

            $p13nClient = new Boxalino_CemSearch_Helper_P13n_Adapter($p13nConfig);
            $p13nClient->createRecommendation($account, $authData, $language, $entityIdFieldName, true);
            $this->results = $p13nClient->getPersonalRecommendations($widgets, $returnFields, $widgetType);
        }

        // Added check in order to avoid PHP notice
        if (!array_key_exists($widgetName, $this->results)) {
            return null;
        }

        return $this->results[$widgetName];
    }

    private function prepareWidgets($widgetType)
    {
        $widgets = array();
        $recommendations = Mage::getStoreConfig('Boxalino_Recommendation');
        foreach ($recommendations as $recommendation) {
            if (
                (!empty($recommendation['min']) || $recommendation['min'] >= 0) &&
                (!empty($recommendation['max']) || $recommendation['max'] >= 0) &&
                !empty($recommendation['scenario']) &&
                ($recommendation['min'] <= $recommendation['max']) &&
                $recommendation['status'] == true
            ) {
                if ($recommendation['scenario'] == $widgetType) {
                    $widgets[] = array('name' => $recommendation['widget'], 'min_recs' => $recommendation['min'], 'max_recs' => $recommendation['max']);
                }
            }
        }
        return $widgets;
    }
}
