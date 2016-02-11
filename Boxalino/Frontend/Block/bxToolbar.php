<?php
namespace Boxalino\Frontend\Block;
use Magento\Catalog\Block\Product\ProductList\Toolbar;
use Magento\Catalog\Helper\Product\ProductList;
use Magento\Catalog\Model\Product\ProductList\Toolbar as ToolbarModel;
class bxToolbar extends Toolbar{

    protected $p13Helper;
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Catalog\Model\Config $catalogConfig,
        ToolbarModel $toolbarModel,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        ProductList $productListHelper,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Boxalino\Frontend\Helper\P13n\Adapter $p13nHelper,
        array $data = [])
    {
        $this->p13Helper = $p13nHelper;
        parent::__construct($context, $catalogSession, $catalogConfig, $toolbarModel, $urlEncoder, $productListHelper, $postDataHelper, $data);
    }

    public function hasSubPhrases(){
        return $this->p13Helper->areThereSubPhrases();
    }

}


?>
