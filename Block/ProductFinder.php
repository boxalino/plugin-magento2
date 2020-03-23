<?php
namespace Boxalino\Intelligence\Block;

/**
 * Class ProductFinder
 * @package Boxalino\Intelligence\Block
 */
class ProductFinder extends \Magento\Framework\View\Element\Template {

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    protected $bxHelperData;

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    protected $p13nHelper;

    protected $urlModel;

    /**
     * ProductFinder constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param \Boxalino\Intelligence\Helper\Autocomplete $bxAutoCompleteHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper,
        \Magento\Framework\Url $urlInterface,
        array $data = []
    )
    {
        $this->bxHelperData = $bxHelperData;
        $this->p13nHelper = $p13nHelper;
        $this->urlModel = $urlInterface;
        parent::__construct($context, $data);
    }

    /**
     * @return com\boxalino\bxclient\v1\BxFacets
     */
    public function getBxFacets() {
        return $this->p13nHelper->getFacets(true);
    }

    /**
     * @return array|mixed
     */
    public function getFieldNames() {
        return $this->getBxFacets()->getFacetExtraInfoFacets('finderFacet', 'true', false, false);
    }

    /**
     *
     */
    protected function checkMode() {
        $currentUrl = $this->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true]);
        if(strpos($currentUrl, $this->getData('finder_url_pattern')) !== false){
            $this->bxHelperData->setIsFinder(true);
        }
    }

    public function test() {
        return $this->getJSONData();
    }
    /**
     * @return string
     */
    public function getJSONData() {
        $this->checkMode();
        $json = [];
        $fieldNames = $this->getFieldNames();
        if(!empty($fieldNames)) {
            $bxFacets = $this->getBxFacets();
            foreach ($fieldNames as $fieldName) {
                if($fieldName == ''){
                    continue;
                }
                $facetExtraInfo = $bxFacets->getAllFacetExtraInfo($fieldName);
                $extraInfo = [];
                $facetValues = $bxFacets->getFacetValues($fieldName);
                $json['facets'][$fieldName]['facetValues'] = $facetValues;
                foreach ($facetValues as $value) {
                    if($bxFacets->isFacetValueHidden($fieldName, $value)) {
                        $json['facets'][$fieldName]['hidden_values'][] = $value;
                    }
                }
                $json['facets'][$fieldName]['label'] = $bxFacets->getFacetLabel($fieldName, $this->getLocale());
                foreach ($facetExtraInfo as $info_key => $info) {
                    if($info_key == 'isSoftFacet' && $info == null){
                        $facetMapping = [];
                        $attributeName = substr($fieldName, 9);
                        $json['facets'][$fieldNames]['parameterName'] = $attributeName;
                        $attributeModel =  $this->_modelConfig->getAttribute('catalog_product', $attributeName)->getSource();
                        $options = $attributeModel->getAllOptions(false);
                        $responseValues =  $this->bxHelperData->useValuesAsKeys($json['facets'][$fieldName]['facetValues']);
                        foreach ($options as $option){
                            $label = is_array($option) ? $option['label'] : $option;
                            if(isset($responseValues[$label])){
                                $facetMapping[$label] = $option['value'];
                            }
                        }
                        $json['facets'][$fieldName]['facetMapping'] = $facetMapping;
                    }
                    if($info_key == 'jsonDependencies' || $info_key == 'label' || $info_key == 'iconMap' || $info_key == 'facetValueExtraInfo') {
                        $info = json_decode($info);
                        if($info_key == 'jsonDependencies') {
                            if(!is_null($info)) {
                                if(isset($info[0]) && isset($info[0]->values[0])) {
                                    $check = $info[0]->values[0];
                                    if(strpos($check, ',') !== false) {
                                        $info[0]->values = explode(',', $check);
                                    }
                                }
                            }
                        }
                    }
                    $extraInfo[$info_key] = $info;
                }
                $json['facets'][$fieldName]['facetExtraInfo'] = $extraInfo;
            }

            $json['separator'] = $this->bxHelperData->getSeparator();
            $json['level'] = $this->getFinderLevel();
            $json['parametersPrefix'] = $this->getUrlParameterPrefix();
            $json['contextParameterPrefix'] = $this->getParametersPrefix();
        }
        return json_encode($json);
    }

    public function getLocale() {
        return 'en';
    }

    public function getFinderLevel() {
        $ids = $this->p13nHelper->getEntitiesIds();
        $level = 10;
        $h = 0;
        foreach ($ids as $id) {
            if($this->p13nHelper->getHitVariable($id, 'highlighted')){
                if($h++ >= 2){
                    $level = 5;
                    break;
                }
            }
            if($h == 0) {
                $level = 1;
                break;
            } else {
                break;
            }
        }
        return $level;
    }

    /**
     *
     */
    public function getUrlParameterPrefix() {
        return $this->p13nHelper->getUrlParameterPrefix();
    }

    /**
     * @return string
     */
    public function getParametersPrefix() {
        return $this->p13nHelper->getPrefixContextParameter();
    }

}

