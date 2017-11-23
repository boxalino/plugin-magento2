<?php

namespace Boxalino\Intelligence\Block\Product\ProductList;

/**
 * Class Blog
 * @package Boxalino\Intelligence\Block
 */
class Blog extends \Boxalino\Intelligence\Block\BxRecommendationBlock implements \Magento\Framework\DataObject\IdentityInterface {

  protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

  protected $_logger;

  protected $bxHelperData;

  protected $p13nHelper;

    /**
     * Blog constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory $factory,
        \Magento\Framework\App\Request\Http $request,
        array $data
    )
    {
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
      return $this->bxHelperData->getCmsRecommendationBlocks($content);
    }

    public function getReturnFields() {
      $fields = array(
        'title',
        $this->getExcerptFieldName(),
        $this->getLinkFieldName(),
        $this->getMediaUrlFieldName(),
        $this->getDateFieldName()

      );

      $extraFields = $this->getExtraFieldNames();

      return array_merge($fields, $extraFields);
    }

    public function isActive(){
      return $this->bxHelperData->isBlogRecommendationEnabled();
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

    public function getBlogArticleTitle(){

      return $this->p13nHelper->getClientResponse()->getResultTitle($this->getChoiceId());

    }

    public function getBlogArticles() {
       $articles = array();
       foreach($this->p13nHelper->getClientResponse()->getHitFieldValues($this->getReturnFields(), $this->getChoiceId()) as $article) {
         $a = array();
         foreach($article as $k => $v) {
           $a[$k] = isset($v[0]) ? $v[0] : '';
         }
         $articles[] = $a;
       }
       return $articles;
    }

}
