<?php
namespace Boxalino\Intelligence\Block;
use Magento\Catalog\Block\Product\ProductList\Toolbar;
use Magento\Catalog\Helper\Product\ProductList;
use Magento\Catalog\Model\Product\ProductList\Toolbar as ToolbarModel;
class BxToolbar extends Toolbar{

    protected $p13Helper;
    protected $bxHelperData;
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Catalog\Model\Config $catalogConfig,
        ToolbarModel $toolbarModel,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        ProductList $productListHelper,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        array $data = [])
    {
        $this->p13Helper = $p13nHelper;
        $this->bxHelperData = $bxHelperData;
        parent::__construct($context, $catalogSession, $catalogConfig, $toolbarModel, $urlEncoder, $productListHelper, $postDataHelper, $data);
    }

    public function hasSubPhrases(){
        if($this->bxHelperData->isSearchEnabled()){
            return $this->p13Helper->areThereSubPhrases();
        }
        return 0;
    }
}?>
