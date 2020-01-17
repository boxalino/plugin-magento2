<?php
namespace Boxalino\Intelligence\Block;

/**
 * Class SearchEmpty
 *
 * @package Boxalino\Intelligence\Block\Search
 */
class SearchEmpty extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    protected $bxHelperData;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        array $data = []
    ){
        $this->bxHelperData = $bxHelperData;
        parent::__construct($context, $data);

    }

    /**
     * Check if the empty search feature is enabled
     * @return bool
     */
    public function isEnabled()
    {
        return $this->bxHelperData->isEmptySearchEnabled();
    }

    /**
     * Check if the empty search feature is enabled
     * @return bool
     */
    public function getReplacementQuery()
    {
        return $this->bxHelperData->getEmptySearchQueryReplacement();
    }

}
