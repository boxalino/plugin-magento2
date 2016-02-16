<?php
namespace Boxalino\Frontend\Block;
class BxAbstract extends \Magento\Framework\View\Element\AbstractBlock
{
    protected $helperData;
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Boxalino\Frontend\Helper\Data $helperData,
        array $data=[]
    )
    {
        $this->helperData = $helperData;
        parent::__construct($context, $data);
    }

    public function isPluginEnabled()
    {
        return $this->helperData->isEnabled();
    }

    public function isPageEnabled($uri)
    {
        return $this->helperData->isPageEnabled($uri);
    }

    public function getSearchUrl()
    {
        return $this->getUrl('Boxalino_CemSearch/search');
    }

    public function getLanguage()
    {
        return $this->helperData->getLanguage();
    }

    public function getSuggestUrl()
    {
        return $this->helperData->getSuggestUrl();
    }

    public function getSuggestParameters()
    {
        return $this->helperData->getSuggestParameters();
    }


}
