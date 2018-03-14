<?php

namespace Boxalino\Intelligence\Controller\Category;

class View extends \Magento\Catalog\Controller\Category\View{

    protected $p13nHelper;

    protected $bxHelperData;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\Design $catalogDesign,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator $categoryUrlPathGenerator,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData

    ) {
        parent::__construct($context, $catalogDesign, $catalogSession, $coreRegistry,
            $storeManager, $categoryUrlPathGenerator, $resultPageFactory, $resultForwardFactory,
            $layerResolver, $categoryRepository);
        $this->p13nHelper = $p13nHelper;
        $this->bxHelperData = $bxHelperData;
    }

    public function execute()
    {
        try{
            if($this->bxHelperData->isNavigationEnabled()) {
                $this->_initCategory();
                if($this->p13nHelper->getResponse()->getRedirectLink() != "") {
                    $this->getResponse()->setRedirect($this->p13nHelper->getResponse()->getRedirectLink());
                }
                $this->_coreRegistry->unregister('current_category');
            }
        } catch (\Exception $e) {
            $this->bxHelperData->setFallback(true);
            $this->_logger->critical($e);
        }
        return parent::execute();
    }
}
