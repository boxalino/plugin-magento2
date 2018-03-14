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
                $start = microtime(true);
                $this->p13nHelper->addNotification('debug', "request start at " . $start);

                if($this->p13nHelper->getResponse()->getRedirectLink() != "") {
                    $this->getResponse()->setRedirect($this->p13nHelper->getResponse()->getRedirectLink());
                }

                $this->p13nHelper->addNotification('debug',
                    "request end, time: " . (microtime(true) - $start) * 1000 . "ms" .
                    ", memory: " . memory_get_usage(true));
                $this->_coreRegistry->unregister('current_category');

                $start = microtime(true);
                $parent_return = parent::execute();
                $this->p13nHelper->addNotification('debug',
                    "Page rendering end, time: " . (microtime(true) - $start) * 1000 . "ms" .
                    ", memory: " . memory_get_usage(true));
            }
        } catch (\Exception $e) {
            $this->bxHelperData->setFallback(true);
            $this->_logger->critical($e);
        }
        $parent_return = isset($parent_return) ? $parent_return : parent::execute();
        return $parent_return;
    }
}
