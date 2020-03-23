<?php

namespace Boxalino\Intelligence\Block;

/**
 * Class Facets
 * @package Boxalino\Intelligence\Block
 */
class Notification extends \Magento\Framework\View\Element\Template{

    /**
    * @var \Boxalino\Intelligence\Api\P13nAdapterInterface
     */
    private $p13nHelper;

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    private $bxHelperData;

    /**
     * Notification constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
    * @param \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
         \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->p13nHelper = $p13nHelper;
        $this->bxHelperData = $bxHelperData;
    }

    /**
     * Display BxNotification for debugging purposes
     */
    public function displayNotification() {
        $this->p13nHelper->finalNotificationCheck();
    }

}
