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
        return $this->p13nHelper->getClientResponse()->getHitFieldValues(array('title'), $this->_data['widget']);
    }
}
