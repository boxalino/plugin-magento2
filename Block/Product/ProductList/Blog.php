<?php

namespace Boxalino\Intelligence\Block\Product\ProductList;

/**
 * Class Blog
 * @package Boxalino\Intelligence\Block
 */
class Blog extends \Boxalino\Intelligence\Block\BxRecommendationBlock
    implements \Magento\Framework\DataObject\IdentityInterface
{

    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    protected $_logger;
    protected $bxHelperData;
    protected $p13nHelper;

    /**
     * Blog constructor.
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility
     * @param \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory $factory
     * @param \Magento\Framework\App\Request\Http $request
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory $factory,
        \Magento\Framework\App\Request\Http $request,
        array $data = []
    ){
        $this->_logger = $context->getLogger();
        $this->p13nHelper = $p13nHelper;
        $this->bxHelperData = $bxHelperData;
        parent::__construct($context,
            $p13nHelper,
            $bxHelperData,
            $checkoutSession,
            $catalogProductVisibility,
            $factory,
            $request,
            $data);

    }

    protected function _prepareData(){
        return $this;
    }

    public function getCmsRecommendationBlocks($content) {
        if($this->isActive() == false) {
            return [];
        }
        return $this->bxHelperData->getCmsRecommendationBlocks($content);
    }

    public function getReturnFields() {
        return $this->bxHelperData->getBlogReturnFields();
    }

    public function showBlock() {
        return $this->p13nHelper->getClientResponse()->getTotalHitCount($this->getChoiceId());
    }

    public function isActive(){
        return $this->bxHelperData->isBlogRecommendationEnabled() && $this->bxHelperData->isPluginEnabled();
    }

    public function getExcerptFieldName() {
        return $this->bxHelperData->getExcerptFieldName();
    }

    public function getLinkFieldName() {
        return $this->bxHelperData->getLinkFieldName();
    }

    public function getMediaUrlFieldName() {
        return $this->bxHelperData->getMediaUrlFieldName();
    }

    public function getDateFieldName() {
        return $this->bxHelperData->getDateFieldName();
    }

    public function getExtraFieldNames() {
        return $this->bxHelperData->getExtraFieldNames();
    }

    public function getBlogArticleImageWidth() {
        return $this->bxHelperData->getBlogArticleImageWidth();
    }

    public function getBlogArticleImageHeight() {
        return $this->bxHelperData->getBlogArticleImageHeight();
    }

    public function getChoiceId() {
        return $this->bxHelperData->getBlogRecommendationChoiceId();
    }

    public function getBlogArticleTitle(){

        return $this->p13nHelper->getClientResponse()->getResultTitle($this->getChoiceId());

    }

    public function getBlogArticles() {
        $articles = [];
        $blog_result = $this->p13nHelper->getClientResponse()->getHitFieldValues($this->getReturnFields(), $this->getChoiceId());
        if($blog_result) {
            foreach($blog_result as $article) {
                $a = [];
                foreach($article as $k => $v) {
                    $a[$k] = isset($v[0]) ? $v[0] : '';
                }
                $excerpt = strip_tags($a['products_blog_excerpt']);
                $excerpt = str_replace('[&hellip;]', '...', $excerpt);
                $a['products_blog_excerpt'] = $excerpt;
                $articles[] = $a;
            }
        }
        return $articles;
    }

    /**
     * @return string|null
     */
    public function getRequestUuid()
    {
        if($this->bxHelperData->isBlogEnabled() && $this->bxHelperData->isPluginEnabled())
        {
            return $this->p13nHelper->getRequestUuid();
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getRequestGroupBy()
    {
        if($this->bxHelperData->isBlogEnabled() && $this->bxHelperData->isPluginEnabled())
        {
            return $this->p13nHelper->getRequestGroupBy();
        }

        return null;
    }

}
