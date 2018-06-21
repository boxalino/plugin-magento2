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
      if ($this->bxHelperData->isPluginEnabled()) {
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

    // javascript config from extra info

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

    // get values from extra info

    public function addOverlayRequests(){
      $this->p13nHelper->addOverlayRequests();
    }

    public function getOverlayValues($key){
      $overlayWidget = $this->p13nHelper->getOverlayChoice();
      return $this->p13nHelper->getResponse()->getExtraInfo($key, '', $overlayWidget);
    }

    public function getOverlayTitle(){

      $overlayTitleObject = $this->getOverlayValues('extend_localized_title');

      // decodes the object, converts it to an array and then uses the language as the key
      $overlayTitle = json_decode($overlayTitleObject, true)[0][$this->getLanguage()];

      return $overlayTitle;

    }

    public function getOverlayBackground(){

      return $this->getOverlayValues('bx_extend_background');

    }

    public function getOverlayText(){

      $overlayTextObject = $this->getOverlayValues('extend_localized_text');

      // decodes the object, converts it to an array and then uses the language as the key
      $overlayText = json_decode($overlayTextObject, true)[0][$this->getLanguage()];

      return $overlayText;

    }

    public function getOverlayButton(){

      $overlayButtonObject = $this->getOverlayValues('extend_localized_button');

      // decodes the object, converts it to an array and then uses the language as the key
      $overlayButton = json_decode($overlayButtonObject, true)[0][$this->getLanguage()];

      return $overlayButton;

    }

    public function getOverlayUrl(){

      $overlayUrlObject = $this->getOverlayValues('extend_localized_url');

      // decodes the object, converts it to an array and then uses the language as the key
      $overlayUrl = json_decode($overlayUrlObject, true)[0][$this->getLanguage()];

      return $overlayUrl;

    }

    public function getOverlayPosition(){

      return $this->getOverlayValues('bx_extend_position');

    }

    public function getLanguage(){
      return $this->bxHelperData->getLanguage();
    }

    // lightbox effect

    public function withLightboxEffect(){

      return $this->getOverlayValues('bx_extend_lightbox');

    }
}
