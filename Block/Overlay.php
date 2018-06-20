<?php

namespace Boxalino\Intelligence\Block;

/**
 * Class Overlay
 * @package Boxalino\Intelligence\Block
 */
class Overlay extends \Magento\Framework\View\Element\Template{

    /**
     * @var \Boxalino\Intelligence\Helper\P13n\Adapter
     */
    private $p13nHelper;

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    private $bxHelperData;

    /**
     * SearchMessage constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        array $data = []
    )
    {

        parent::__construct($context, $data);
        $this->p13nHelper = $p13nHelper;
        $this->bxHelperData = $bxHelperData;

    }

    public function isActive(){

        if ($this->bxHelperData->isPluginEnabled()) {

            return true;

        }

    }

    public function getOverlayValues($key){

      return $this->p13nHelper->getOverlayValues($key, $this->_data['widget']);

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

    public function getOverlayTimeout(){

      //$timeout is the time in seconds (e.g. 3), has to be multiplied by 1000 (milliseconds) for js function 'setTimeout'
      $timeout = $this->getOverlayValues('bx_extend_timeout');

      if ($timeout) {
        return ($timeout * 1000);
      }else{
        return 5000;
      }

    }

    public function getOverlayExitIntendTimeout(){

      $timeout = $this->getOverlayValues('bx_extend_exit_intend_timeout');

      if (!empty($timeout)) {
        return $timeout;
      }else{
        return 5;
      }

    }

    public function getOverlayPosition(){

      return $this->getOverlayValues('bx_extend_position');

    }

    public function getOverlayFrequency(){

      $frequency = $this->getOverlayValues('bx_extend_frequency');

      if (!empty($frequency)) {
        return $frequency;
      }else{
        return 0;
      }

    }

    public function getOverlayEvent(){

      return $this->getOverlayValues('bx_extend_event');

    }

    public function withLightboxEffect(){

      return $this->getOverlayValues('bx_extend_lightbox');

    }

    public function getLanguage(){

      return $this->bxHelperData->getLanguage();

    }

}
