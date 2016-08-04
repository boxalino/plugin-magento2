<?php
namespace Boxalino\Intelligence\Block;

class CmsBlock extends \Magento\Cms\Block\Block{

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    private $bxHelperData;

    /**
     * CmsBlock constructor.
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Magento\Cms\Model\Template\FilterProvider $filterProvider
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Cms\Model\BlockFactory $blockFactory
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context, 
        \Magento\Cms\Model\Template\FilterProvider $filterProvider, 
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Cms\Model\BlockFactory $blockFactory, 
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        array $data = []
    )
    {
        $this->bxHelperData = $bxHelperData;
        parent::__construct($context, $filterProvider, $storeManager, $blockFactory, $data);
    }

    /**
     * @return string
     */
    protected function _toHtml(){
        
        $blockId = $this->getBlockId();
        $html = '';
        if ($blockId) {
            $storeId = $this->_storeManager->getStore()->getId();
            /** @var \Magento\Cms\Model\Block $block */
            $block = $this->_blockFactory->create();
            $block->setStoreId($storeId)->load($blockId);
            if ($block->isActive()) {
                if(strpos($block->getContent(),'BxRecommendationBlock')){
                    $this->bxHelperData->setCmsBlock($block->getContent());
                }
                $html = $this->_filterProvider->getBlockFilter()->setStoreId($storeId)->filter($block->getContent());
            }
        }
        return $html;
    }
}