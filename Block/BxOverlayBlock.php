<?php

namespace Boxalino\Intelligence\Block;

/**
 * Class OverlayBlock
 * @package Boxalino\Intelligence\Block
 */
class BxOverlayBlock extends \Magento\Framework\View\Element\Template {

    /**
     * @var \Boxalino\Intelligence\Helper\P13n\Adapter
     */
    protected $p13nHelper;

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    protected $bxHelperData;

    /**
     * @var \Boxalino\Intelligence\Helper\ResourceManager
     */
    protected $resourceManager;

    /**
     * @var \Boxalino\Intelligence\Helper\ResourceManager
     */
    protected $_objectManager;

    /**
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Boxalino\Intelligence\Helper\ResourceManager $bxResourceManager,
        array $data = []
        )

        {

        $this->_logger = $context->getLogger();
        $this->p13nHelper = $p13nHelper;
        $this->bxHelperData = $bxHelperData;
        $this->bxResourceManager = $bxResourceManager;
        parent::__construct($context, $data);

        if ($this->isActive() == true) {
          $this->addOverlayRequests();
        }

    }

    public function isActive(){
      if ($this->bxHelperData->isOverlayEnabled()) {
          return true;
      }
      return false;
    }

    public function getControllerUrl(){

      return $this->_storeManager->getStore()->getBaseUrl() . 'bxGenericRecommendations/index/setproperties/';

    }

    public function getGrandTotal(){

      $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
      $cart = $objectManager->get('\Magento\Checkout\Model\Cart');

      $grandTotal = $cart->getQuote()->getGrandTotal();

      if (empty($grandTotal)) {
        return 0;
      } else {
        return $grandTotal;
      }

    }

    // get template parameters from response

    public function getVariantIndex(){
      return $this->p13nHelper->getOverlayVariantId();
    }

    public function getTemplatePathFromResponse(){
      return $this->getOverlayValues('bx-template-path');
    }

    public function getBlockPathFromResponse(){
      return $this->getOverlayValues('bx-block-path');
    }

    // javascript config from response

    public function getOverlayJsParameters(){
      return $this->getOverlayValues('bx-extend-parameters');
    }

    public function getOverlayBehaviorJs(){
      return $this->getOverlayValues('bx-extend-behaviour');
    }

    public function getOverlayExtraParams(){
      $paramsJson = $this->getOverlayValues('bx-extend-extra-params');
      if (!empty($paramsJson)) {
        return $paramsJson;
      }else{
        return 0;
      }
    }

    public function addOverlayRequests(){
      $hitcount = $this->getOverlayHitcount();
      $overlayBannerChoiceHitCount = $this->getOverlayBannerChoiceHitCount();
      $order = $this->getOverlayOrder();
      $dir = $this->getOverlayDir();
      $pageOffset = $this->getOverlayPageOffset();
      $this->p13nHelper->addOverlayRequests($hitcount, $overlayBannerChoiceHitCount, $order, $dir, $pageOffset);
    }

    public function getOverlayHitcount(){
      $hitcount = $this->bxHelperData->getOverlayHitcount();
      if (!empty($hitcount)) {
        return $hitcount;
      }
      return 3;
    }
    public function getOverlayBannerChoiceHitCount(){
      $bannerHitcount = $this->bxHelperData->getOverlayBannerChoiceHitcount();
      if (!empty($bannerHitcount)) {
        return $bannerHitcount;
      }
      return 1;
    }
    public function getOverlayOrder(){
      $order = $this->bxHelperData->getOverlayOrder();
      if (!empty($order)) {
        return $order;
      }
      return null;
    }
    public function getOverlayDir(){
      $dir = $this->bxHelperData->getOverlayDir();
      if (!empty($dir)) {
        return $dir;
      }
      return null;
    }
    public function getOverlayPageOffset(){
      $pageoffset = $this->bxHelperData->getOverlayPageOffset();
      if (!empty($pageoffset)) {
        return $pageoffset;
      }
      return null;
    }

    public function getOverlayValues($key){
      $overlayWidget = $this->p13nHelper->getOverlayChoice();
      return $this->p13nHelper->getClientResponse()->getExtraInfo($key, '', $overlayWidget);
    }

    public function getLanguage(){
      return $this->bxHelperData->getLanguage();
    }

    // lightbox effect

    public function withLightboxEffect(){

      return $this->getOverlayValues('bx_extend_lightbox');

    }
}
