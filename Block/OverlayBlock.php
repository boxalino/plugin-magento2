<?php

namespace Boxalino\Intelligence\Block;

/**
 * Class OverlayBlock
 * @package Boxalino\Intelligence\Block
 */
class OverlayBlock extends BxBannerBlock implements \Magento\Framework\DataObject\IdentityInterface {

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
        \Magento\Catalog\Block\Product\Context $context,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory $factory,
        \Magento\Framework\App\Request\Http $request,
        array $data,
        \Boxalino\Intelligence\Helper\ResourceManager $bxResourceManager
        )

        {

        $this->_logger = $context->getLogger();
        parent::__construct($context,
                            $p13nHelper,
                            $bxHelperData,
                            $checkoutSession,
                            $catalogProductVisibility,
                            $factory,
                            $request,
                            $data,
                            $bxResourceManager
                            );
        $this->p13nHelper = $p13nHelper;
        $this->bxHelperData = $bxHelperData;
        $this->bxResourceManager = $bxResourceManager;

        $this->addOverlayRequests();

    }

    public function isActive(){
      if ($this->bxHelperData->isOverlayEnabled()) {
          return true;
      }
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

    public function getVariantIndexFromResponse(){
      return $this->getOverlayValues('bx-variant-index');
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
      $this->p13nHelper->addOverlayRequests();
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
