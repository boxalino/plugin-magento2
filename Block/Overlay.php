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

      return $this->getOverlayValues('bx_extend_title');

    }

    public function getOverlayBackground(){

      return $this->getOverlayValues('bx_extend_background');

    }

    public function getOverlayText(){

      return $this->getOverlayValues('bx_extend_text');

    }

    public function getOverlayButton(){

      return $this->getOverlayValues('bx_extend_button');

    }

    public function getOverlayUrl(){

      return $this->getOverlayValues('bx_extend_url');

    }

    public function getOverlayTimeout(){

      //$timeout is the time in seconds (e.g. 3), has to be multiplied by 1000 (milliseconds) for js function 'setTimeout'
      $timeout = $this->getOverlayValues('bx_extend_timeout');

      if (!empty($timeout)) {
        return ($timeout * 1000);
      }else{
        return 5000;
      }

    }

}
