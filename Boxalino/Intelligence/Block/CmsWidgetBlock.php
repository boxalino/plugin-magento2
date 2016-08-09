<?php 
namespace Boxalino\Intelligence\Block;

class CmsWidgetBlock extends \Magento\Cms\Block\Widget\Block
{

    protected $bxHelperData;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Cms\Model\BlockFactory $blockFactory,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        array $data
    )
    {
        $this->bxHelperData = $bxHelperData;
        parent::__construct($context, $filterProvider, $blockFactory, $data);
    }

    public function _beforeToHtml()
    {
        $blockId = $this->getData('block_id');
        $blockHash = get_class($this) . $blockId;

        if (isset(self::$_widgetUsageMap[$blockHash])) {
            return $this;
        }
        self::$_widgetUsageMap[$blockHash] = true;

        if ($blockId) {
            $storeId = $this->_storeManager->getStore()->getId();
            /** @var \Magento\Cms\Model\Block $block */
            $block = $this->_blockFactory->create();
            $block->setStoreId($storeId)->load($blockId);
            $this->bxHelperData->setCmsBlock($block->getContent());
            if ($block->isActive()) {
                $this->setText(
                    $this->_filterProvider->getBlockFilter()->setStoreId($storeId)->filter($block->getContent())
                );
            }
        }

        unset(self::$_widgetUsageMap[$blockHash]);
        return $this;
    }
}