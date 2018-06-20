<?php

namespace Boxalino\Intelligence\Block\Journey\Banner;

use \Boxalino\Intelligence\Block\Journey\CPOJourney as CPOJourney;

/**
 * Class JssorBanner
 * @package Boxalino\Intelligence\Block\Journey\Banner
 */
class JssorBanner extends \Magento\Framework\View\Element\Template implements CPOJourney{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Boxalino\Intelligence\Block\BxJourney
     */
    protected $bxJourney;

    /**
     * @var \Boxalino\Intelligence\Helper\P13n\Adapter
     */
    protected $p13nHelper;

    /**
     * Text constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Boxalino\Intelligence\Block\BxJourney $journey,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_logger = $context->getLogger();
        $this->bxJourney = $journey;
        $this->p13nHelper = $p13nHelper;
    }

    public function getSubRenderings()
    {
        $elements = array();
        $element = $this->getData('bxVisualElement');
        if(isset($element['subRenderings'][0]['rendering']['visualElements'])) {
            $elements = $element['subRenderings'][0]['rendering']['visualElements'];
        }
        return $elements;
    }

    public function renderVisualElement($element, $additional_parameter = null)
    {
        return $this->bxJourney->createVisualElement($element, $additional_parameter)->toHtml();
    }

    public function getLocalizedValue($values) {
        return $this->p13nHelper->getClientResponse()->getLocalizedValue($values);
    }

    public function bxGetBanner() {
        $visualElement = $this->getData('bxVisualElement');
        $bannerData = array();
        $variant_index = null;
        foreach ($visualElement['parameters'] as $parameter) {
            if($parameter['name'] == 'variant') {
                $variant_index = reset($parameter['values']);
                break;
            }
        }
        if(!is_null($variant_index)) {
            $bannerData['hitCount'] = sizeof($this->p13nHelper->getClientResponse()->getHitIds(null, $variant_index));
            $bannerData['bannerLayout'] = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_layout', '', null, true, $variant_index);
            $bannerData['bannerTitle'] = $this->p13nHelper->getClientResponse()->getResultTitle(null, $variant_index);
            $bannerData['bannerId'] = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_id', '', null, true, $variant_index);

            $slides = $this->p13nHelper->getClientResponse()->getHitFieldValues(array('products_bxi_bxi_jssor_slide', 'products_bxi_bxi_name'), null, true, $variant_index);
            $counters = array();
            foreach($slides as $id => $val) {
                $slides[$id]['div'] = $this->getBannerSlide($id, $val, $counters);
            }
            if ($bannerData['bannerLayout'] != 'large') {
                if ($this->getData('jssorIndex') == '1') {
                    return array(reset($slides));
                }
                if ($this->getData('jssorIndex') == '2') {
                    return array(end($slides));
                }
            }
            $bannerData['bannerSlides'] = $slides;

            $bannerData['bannerTransitions'] = $this->getBannerJssorSlideGenericJS('products_bxi_bxi_jssor_transition', $variant_index);
            $bannerData['bannerBreaks'] = $this->getBannerJssorSlideGenericJS('products_bxi_bxi_jssor_break', $variant_index);
            $bannerData['bannerControls'] = $this->getBannerJssorSlideGenericJS('products_bxi_bxi_jssor_control', $variant_index);
            $bannerJssorOptions = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_options', '', null, true, $variant_index);
            // replace id from Intelligence with id from block configuration
            $bannerData['bannerOptions'] = str_replace($bannerData['bannerId'], $this->getData('jssorID'), $bannerJssorOptions);
            $bannerData['bannerMaxWidth'] = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_max_width', '', null, true, $variant_index);
            $bannerJssorCss = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_css', '', null, true, $variant_index);
            // replace id from Intelligence with id from block configuration
            $bannerJssorCss = str_replace($bannerData['bannerId'], $this->getData('jssorID'), $bannerJssorCss);
            $bannerData['bannerCSS'] = str_replace("JSSORID", $bannerData['bannerId'], $bannerJssorCss);

            $bannerData['bannerStyle'] = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_style', '', null, true, $variant_index);
            $bannerData['bannerSlidesStyle'] = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_slides_style', '', null, true, $variant_index);
            $bannerData['bannerLoadingScreen'] = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_loading_screen', '', null, true, $variant_index);
            $bannerData['bannerBulletNavigator'] = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_bullet_navigator', '', null, true, $variant_index);
            $bannerData['bannerArrowNavigator'] = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_arrow_navigator', '', null, true, $variant_index);
            $bannerFunction = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_function', '', null, true, $variant_index);
            $bannerData['bannerFunction'] = str_replace($bannerData['bannerId'], $this->getData('jssorID'), $bannerFunction);
        }

        return $bannerData;
    }

    protected function getBannerJssorSlideGenericJS($key, $variant_id) {
        $language = $this->p13nHelper->getLanguage();
        $slides = $this->p13nHelper->getClientResponse()->getHitFieldValues(array($key), null, true, $variant_id);
        $jsArray = array();
        foreach($slides as $id => $vals) {
            if(isset($vals[$key]) && sizeof($vals[$key]) > 0) {

                $jsons = json_decode($vals[$key][0], true);
                if(isset($jsons[$language])) {
                    $json = $jsons[$language];
                    //fix some special case an extra '}' appears wrongly at the end
                    $minus = 2;
                    if(substr($json, strlen($json)-1, 1) == '}') {
                        $minus = 3;
                    }
                    //removing the extra [] around
                    $json = substr($json, 1, strlen($json)-$minus);
                    $jsArray[] = $json;
                }
            }
        }
        return '[' . implode(',', $jsArray) . ']';
    }

    protected function addPrefixToClasses($json){
        $idFromConfig = $this->getData('jssorID');
        $elems = explode(' ' ,$json);
        foreach ($elems as $i => $value) {
            if (preg_match('/bxBanner/', $value)) {
                $value = str_replace('bxBanner', $idFromConfig . '_bxBanner', $value);
                $elems[$i] = $value;
            }
        }
        $json = implode(' ', $elems);
        return $json;
    }

    protected function getBannerSlide($id, $vals, &$counters) {
        $language = $this->p13nHelper->getLanguage();
        if(isset($vals['products_bxi_bxi_jssor_slide']) && sizeof($vals['products_bxi_bxi_jssor_slide']) > 0) {
            $json = $vals['products_bxi_bxi_jssor_slide'][0];

            $slide = json_decode($json, true);
            if(isset($slide[$language])) {
                $json = $slide[$language];

                // add configId as prefix for the classes

                $json = $this->addPrefixToClasses($json);

                for($i=1; $i<10; $i++) {

                    if(!isset($counters[$i])) {
                        $counters[$i] = 0;
                    }

                    $pieces = explode('BX_COUNTER'.$i, $json);
                    foreach($pieces as $j => $piece) {
                        if($j >= sizeof($pieces)-1) {
                            continue;
                        }
                        $pieces[$j] .= $counters[$i]++;
                    }
                    $json = implode('', $pieces);

                }
                return $json;
            }
        }
        return '';
    }
}
