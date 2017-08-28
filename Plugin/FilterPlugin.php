<?php
namespace Boxalino\Intelligence\Plugin;

/**
 * Class Filter
 *
 * plugin for Magento\Widget\Model\Template\Filter
 *
 * @see \Magento\Widget\Model\Template\Filter
 * @author th
 */
class FilterPlugin{
    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    protected $_bxHelperData;

    /**
     * Index constructor.
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Cms\Helper\Page $cmsHelperPage
     */
    public function __construct(
        \Boxalino\Intelligence\Helper\Data $bxHelperData
    ) {
        $this->_bxHelperData = $bxHelperData;
    }

    /**
     * checks if the block content contains a bxRecommendationBlock reference
     *
     * @see \Magento\Widget\Model\Template\Filter::filter()

     * @param string $value
     * @return \Magento\Framework\Controller\Result\Forward
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeFilter($subject, $value) {

        if(strpos($value,'BxRecommendationBlock') !== false){
            $this->_bxHelperData->setCmsBlock($value);
        }
    }
}
