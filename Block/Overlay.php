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

    public function getOverlayValues(){

      return $this->p13nHelper->getOverlayValues($this->_data['widget']);

    }

    public function getOverlayTitle(){

      return $this->getOverlayValues()['bx_extend_title'];

    }

    public function getOverlayBackground(){

      return $this->getOverlayValues()['bx_extend_background'];

    }

    public function getOverlayText(){

      return $this->getOverlayValues()['bx_extend_text'];

    }

    public function getOverlayButton(){

      return $this->getOverlayValues()['bx_extend_button'];

    }

}
