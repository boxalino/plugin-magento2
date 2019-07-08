<?php
namespace Boxalino\Intelligence\Block\Journey\Banner;

use \Boxalino\Intelligence\Block\Journey\CPOJourney as CPOJourney;
use Boxalino\Intelligence\Block\Journey\General;

/**
 * Class JssorBanner
 * @package Boxalino\Intelligence\Block\Journey\Banner
 */
class JssorBanner extends General implements CPOJourney
{

    CONST JSSOR_BANNER_MAX_SIZE = "1238";

    public function bxGetBanner($choiceId = null)
    {
        $visualElement = $this->getData('bxVisualElement');
        $bannerData = [];
        $variant_index = null;
        foreach ($visualElement['parameters'] as $parameter) {
            if($parameter['name'] == 'variant') {
                $variant_index = reset($parameter['values']);
                break;
            }
        }
        if($choiceId != null) { $variant_index++; $choiceId = null; }
        if(!is_null($variant_index)) {
            $choiceId = $this->p13nHelper->getClientResponse()->getChoiceIdFromVariantIndex($variant_index) ;
            $bannerData['hitCount'] = sizeof($this->p13nHelper->getClientResponse()->getHitIds($choiceId, true, 0));
            $bannerData['bannerLayout'] = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_layout', '', $choiceId, true, 0);
            $bannerData['bannerTitle'] = $this->p13nHelper->getClientResponse()->getResultTitle($choiceId, 0);
            $bannerData['bannerId'] = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_id', '', $choiceId, true, 0);
            $jssorId = $this->getData("jssorID");
            if(is_null($jssorId) || empty($jssorId))
            {
                $this->setData("jssorID", $bannerData['bannerId']);
            }

            $slides = $this->p13nHelper->getClientResponse()->getHitFieldValues(array('products_bxi_bxi_jssor_slide', 'products_bxi_bxi_name'), $choiceId, true, 0);
            $counters = [];
            foreach($slides as $id => $val) {
                $slides[$id]['div'] = $this->getBannerSlide($id, $val, $counters, $choiceId);
            }

            if (strpos($bannerData['bannerLayout'],'large')===false || $this->getData('jssorIndex') != null) {
                if ($this->getData('jssorIndex') == '1') {
                    return array(reset($slides));
                }
                if ($this->getData('jssorIndex') == '2') {
                    return array(end($slides));
                }
            }
            $bannerData['bannerSlides'] = $slides;
            $bannerData['bannerTransitions'] = $this->getBannerJssorSlideGenericJS('products_bxi_bxi_jssor_transition', 0, $choiceId);
            $bannerData['bannerBreaks'] = $this->getBannerJssorSlideGenericJS('products_bxi_bxi_jssor_break', 0, $choiceId);
            $bannerData['bannerControls'] = $this->getBannerJssorSlideGenericJS('products_bxi_bxi_jssor_control', 0, $choiceId);
            $bannerData['bannerMaxWidth'] = $this->getBannerMaxWidth($choiceId);
            $bannerData['bannerOptions'] = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_options', '', $choiceId, true, 0);
            $bannerFunction = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_function', '', $choiceId, true, 0);
            $bannerData['bannerFunction'] = $this->getData('jssorID') != null ? str_replace($bannerData['bannerId'], $this->getData('jssorID'), $bannerFunction) : $bannerFunction;
        }

        return $bannerData;
    }

    protected function getBannerMaxWidth($choiceId = null)
    {
        $bannerWidth = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_max_width', '', $choiceId, true, 0);
        if(!empty($bannerWidth))
        {
            return $bannerWidth;
        }

        return self::JSSOR_BANNER_MAX_SIZE;
    }

    protected function getBannerJssorSlideGenericJS($key, $variant_id, $choiceId=null) {
        $language = $this->p13nHelper->getLanguage();
        $slides = $this->p13nHelper->getClientResponse()->getHitFieldValues(array($key), $choiceId, true, 0);
        $jsArray = [];
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

    protected function addPrefixToClasses($json, $choiceId = null){
        $idFromConfig = $this->getData('jssorID');
        if ($idFromConfig == null) {
            $idFromConfig = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_id', '', $choiceId, true, 0);
        }
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

    protected function getBannerSlide($id, $vals, &$counters, $choiceId=null) {
        $language = $this->p13nHelper->getLanguage();
        if(isset($vals['products_bxi_bxi_jssor_slide']) && sizeof($vals['products_bxi_bxi_jssor_slide']) > 0) {
            $json = $vals['products_bxi_bxi_jssor_slide'][0];

            $slide = json_decode($json, true);
            if(isset($slide[$language])) {
                $json = $slide[$language];

                // add configId as prefix for the classes
                $json = $this->addPrefixToClasses($json, $choiceId);

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

    /**
     * replace id from Intelligence with id from block configuration
     * if there is only one banner or it is using the 'small' layout, don't be draggable
     *
     * @param $bannerData
     * @throws \Exception
     */
    public function getBannerOptionsFromData($bannerData)
    {
        $defaultBannerJssorOptions = '{ $AutoPlay: 1, $LazyLoading: 1, $CaptionSliderOptions: { $Class: $JssorCaptionSlideo$, $Transitions: narrativeBanner_SlideoTransitions, $Breaks: narrativeBanner_SlideoBreaks, $Controls: narrativeBanner_SlideoControls }, $ArrowNavigatorOptions: { $Class: $JssorArrowNavigator$ }, $BulletNavigatorOptions: { $Class: $JssorBulletNavigator$ } }';
        $bannerJssorOptions = $bannerData['bannerOptions'];
        if(empty($bannerJssorOptions))
        {
            $bannerJssorOptions = $defaultBannerJssorOptions;
        }
        if($bannerData['hitCount']<2 ||  (!empty($bannerData['bannerLayout']) && strpos($bannerData['bannerLayout'],'large')===false))
        {
            $bannerJssorOptions = substr_replace(preg_replace("/\s+/","",$bannerJssorOptions), '$DragOrientation:0, ', 1, 0);
        }

        $finalOptions =  $this->getData('jssorID') != null ? str_replace($bannerData['bannerId'], $this->getData('jssorID'), $bannerJssorOptions) : $bannerJssorOptions;
        if(empty($finalOptions))
        {
            return "{}";
        }

        return $finalOptions;
    }

    public function getP13nHelper(){
        return $this->p13nHelper;
    }
}
