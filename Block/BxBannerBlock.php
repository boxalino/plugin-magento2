<?php
namespace Boxalino\Intelligence\Block;
/**
 * Class BxRecommendationBlock
 * @package Boxalino\Intelligence\Block
 */
Class BxBannerBlock extends BxRecommendationBlock implements \Magento\Framework\DataObject\IdentityInterface{

    protected function _prepareData(){
        
        return $this;
    }

    protected function prepareRecommendations($recommendations = array(), $returnFields = array()){
        parent::prepareRecommendations($recommendations, array('title'));
    }

    public function isActive(){

        if ($this->bxHelperData->isBannerEnabled()) {
            
            return true;

        }

    }

    public function getBannerSlides() {
        $slides = $this->p13nHelper->getClientResponse()->getHitFieldValues(array('title'), $this->_data['widget']);
        foreach($slides as $id => $vals) {
            $slides[$id]['div'] = $this->getBannerSlide($id);
        }
        return $slides;
    }

    public function getBannerSlide($id) {
        return '<div>
            <img data-u="image" src="img/001.jpg" />
            <div style="position:absolute;top:30px;left:30px;width:480px;height:130px;z-index:0;background-color:rgba(255,188,5,0.8);font-size:40px;font-weight:100;color:#000000;line-height:60px;padding:5px;box-sizing:border-box;">RESPONSIVE SLIDER
            </div>
            <div data-u="caption" data-t="0" style="position:absolute;top:120px;left:75px;width:470px;height:220px;z-index:0;">
                <img style="position:absolute;top:0px;left:0px;width:470px;height:220px;z-index:0;" src="img/c-phone-horizontal.png" />
                <div style="position:absolute;top:4px;left:45px;width:379px;height:213px;z-index:0; overflow:hidden;">
                    <img data-u="caption" data-t="1" style="position:absolute;top:0px;left:0px;width:379px;height:213px;z-index:0;" src="img/c-slide-1.jpg" />
                    <img data-u="caption" data-t="2" style="position:absolute;top:0px;left:379px;width:379px;height:213px;z-index:0;" src="img/c-slide-3.jpg" />
                </div>
                <img style="position:absolute;top:4px;left:45px;width:379px;height:213px;z-index:0;" src="img/c-navigator-horizontal.png" />
                <img data-u="caption" data-t="3" style="position:absolute;top:476px;left:454px;width:63px;height:77px;z-index:0;" src="img/hand.png" />
            </div>
        </div>';
    }

    public function getBannerJssorSlideTransitions() {
        return "[
          [{b:-1,d:1,o:-0.7}],
          [{b:900,d:2000,x:-379,e:{x:7}}],
          [{b:900,d:2000,x:-379,e:{x:7}}],
          [{b:-1,d:1,o:-1,sX:2,sY:2},{b:0,d:900,x:-171,y:-341,o:1,sX:-2,sY:-2,e:{x:3,y:3,sX:3,sY:3}},{b:900,d:1600,x:-283,o:-1,e:{x:16}}]
        ]";
    }

    public function getBannerJssorOptions() {

        $bannerJssorOptions = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_options');

        return $bannerJssorOptions;
    }

    public function getBannerJssorId() {

        $bannerJssorId = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_id');

        return $bannerJssorId;
    }

    public function getBannerJssorMaxWidth() {

        $bannerJssorMaxWidth = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_max_width');

        return $bannerJssorMaxWidth;
    }

    public function getBannerJssorCSS() {

        $bannerJssorCss = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_css');

        return str_replace("JSSORID", $this->getBannerJssorId(), $bannerJssorCss);
    }

    public function getBannerJssorLoadingScreen() {

        $bannerJssorLoadingScreen = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_loading_screen');

        return $bannerJssorLoadingScreen;
    }

    public function getBannerJssorBulletNavigator() {

        $bannerJssorBulletNavigator = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_bullet_navigator');

        return $bannerJssorBulletNavigator;

    }

    public function getBannerJssorArrowNavigator() {

        $bannerJssorArrowNavigator = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_arrow_navigator');

        return $bannerJssorArrowNavigator;

    }
}
