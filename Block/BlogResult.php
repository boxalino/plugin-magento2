<?php
namespace Boxalino\Intelligence\Block;

/**
 * Class BlogResult
 * @package Boxalino\Intelligence\Block
 */
class BlogResult extends \Magento\Framework\View\Element\Template{

    /**
     * @var \Boxalino\Intelligence\Api\P13nAdapterInterface
     */
    private $p13nHelper;

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    private $bxHelperData;

    /**
     * @var null
     */
    private $blogCollection = null;

    /**
     * @var string
     */
    private $blog_page_param = 'bx_blog_page';

    /**
     * Notification constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->p13nHelper = $p13nHelper;
        $this->bxHelperData = $bxHelperData;
    }

    public function getBlogs() {
        if(is_null($this->blogCollection)) {
            $this->prepareBlogCollection();
        }
        return $this->blogCollection;
    }

    public function getPreviousPageUrl() {
        return $this->getPageUrl($this->getPage()-1);
    }

    protected function prepareBlogCollection() {
        $blogs = [];
        $blog_ids = $this->p13nHelper->getBlogIds();
        foreach ($blog_ids as $id) {
            $blog = [];
            foreach ($this->bxHelperData->getBlogReturnFields() as $field) {
                $value = $this->p13nHelper->getHitVariable($id, $field, true);
                $blog[$field] = is_array($value) ? reset($value) : $value;
            }

            if ($blog['products_blog_excerpt']) {
              $excerpt = strip_tags($blog['products_blog_excerpt']);
              $excerpt = str_replace('[&hellip;]', '...', $excerpt);
              $blog['products_blog_excerpt'] = $excerpt;
            }

            $blogs[$id] = $blog;
        }
        $this->blogCollection = $blogs;
    }

    public function getBlogPageParam() {
        return $this->blog_page_param;
    }

    public function getPage() {
        return $this->_request->getParam($this->getBlogPageParam(), 1);
    }

    public function getFirstNum() {
        return ($this->getPageSize() * ($this->getPage() -1)) + 1;
    }

    public function canShowLast() {
        return $this->getPage() == $this->getLastPageNum();
    }

    public function getNextPageUrl() {
        return $this->getPageUrl($this->getPage() + 1);
    }

    public function isPageCurrent($page) {
        return $this->getPage() == $page;
    }

    public function isFirstPage() {
        return $this->getPage() == 1;
    }

    public function getFramePages() {
        return range(1, $this->getLastPageNum());
    }

    public function getLastPageUrl() {
        return $this->getPageUrl($this->getLastPageNum());
    }

    public function canShowFirst() {
        return $this->getPage() > 1 && $this->getLastPageNum() > 1;
    }

    public function canShowNextJump() {
        return $this->getPage() > $this->getLastPageNum();
    }

    public function getNextJumpUrl() {
        return $this->getPageUrl($this->getPage() + 1);
    }

    public function getPageUrl($page) {
        $query = [$this->getBlogPageParam() => $page, 'bx_active_tab' => 'blog'];
        return $this->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true, '_query' => $query]);
    }

    public function canShowPreviousJump() {
        return $this->canShowFirst();
    }

    public function getPageSize() {
        $size = $this->_request->getParam('product_list_limit', $this->p13nHelper->getMagentoStoreConfigPageSize());
        return $size;
    }

    public function getLastNum() {
        return ($this->getPageSize() * ($this->getPage() - 1)) + sizeof($this->getBlogs());
    }

    public function isLastPage() {
        return $this->getPage() == $this->getLastPageNum();
    }

    public function getAnchorTextForPrevious() {
        return $this->_scopeConfig->getValue(
            'design/pagination/anchor_text_for_previous',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAnchorTextForNext() {
        return $this->_scopeConfig->getValue(
            'design/pagination/anchor_text_for_next',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getLastPageNum() {
        $total = $this->getTotalHitCount();
        return ceil($total / $this->getPageSize());
    }

    public function getLinkFieldName() {
        return $this->bxHelperData->getLinkFieldName();
    }

    public function getBlogArticleImageWidth() {
        return $this->bxHelperData->getBlogArticleImageWidth();
    }

    public function getBlogArticleImageHeight() {
        return $this->bxHelperData->getBlogArticleImageHeight();
    }

    public function getMediaUrlFieldName() {
        return $this->bxHelperData->getMediaUrlFieldName();
    }

    public function getExcerptFieldName() {
        return $this->bxHelperData->getExcerptFieldName();
    }

    public function getTotalHitCount(){
        return $this->p13nHelper->getBlogTotalHitCount();
    }

    /**
     * @return string|null
     */
    public function getRequestUuid()
    {
        return $this->p13nHelper->getRequestUuid($this->p13nHelper->getSearchChoice("", true));
    }

    /**
     * @return string|null
     */
    public function getRequestGroupBy()
    {
        return $this->p13nHelper->getRequestGroupBy($this->p13nHelper->getSearchChoice("", true));
    }

}
