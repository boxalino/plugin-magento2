<?php
namespace Boxalino\Intelligence\Block;

/**
 * Class BlogResult
 * @package Boxalino\Intelligence\Block
 */
class BlogResult extends \Magento\Framework\View\Element\Template{

    /**
     * @var \Boxalino\Intelligence\Helper\P13n\Adapter
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
     * Notification constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
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

    protected function prepareBlogCollection() {
        $blog_ids = $this->p13nHelper->getBlogIds();
        foreach ($blog_ids as $id) {
            $blog = array();
            foreach ($this->bxHelperData->getBlogReturnFields() as $field) {
                $value = $this->p13nHelper->getHitVariable($id, $field, true);
                $blog[$field] = is_array($value) ? reset($value) : $value;
            }
            $this->blogCollection[$id] = $blog;
        }
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

}