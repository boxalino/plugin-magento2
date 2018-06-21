<?php

    namespace Boxalino\Intelligence\Block;

    /**
     * Class SetProfile
     * @package Boxalino\Intelligence\Block
     */
    class SetProperties extends \Magento\Framework\View\Element\Template{

        /**
         * @var \Boxalino\Intelligence\Helper\P13n\Adapter
         */
        private $p13nHelper;

        /**
         * @var \Boxalino\Intelligence\Helper\Data
         */
        private $bxHelperData;

        private $bxResponse;

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

        public function sendParametersWithRequest(){

          $this->p13nHelper->sendOverlayRequestWithParams();

        }

      }
