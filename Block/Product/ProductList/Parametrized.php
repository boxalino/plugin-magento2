<?php

namespace Boxalino\Intelligence\Block\Product\ProductList;

/**
 * Class Parametrized
 * @package Boxalino\Intelligence\Block\Product\ProductList
 */
class Parametrized extends \Boxalino\Intelligence\Block\BxRecommendationBlock implements \Magento\Framework\DataObject\IdentityInterface {

  protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

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
        array $data = []
    )
    {
        $this->_logger = $context->getLogger();
        parent::__construct($context,
                            $p13nHelper,
                            $bxHelperData,
                            $checkoutSession,
                            $catalogProductVisibility,
                            $factory,
                            $request,
                            $data);

    }

    public function getChoiceId() {
      return $this->getRequest()->getParam('bx_choice');
    }

    public function getMin() {
      return $this->getRequest()->getParam('bx_min');
    }

    public function getMax() {
      return $this->getRequest()->getParam('bx_max');
    }

    public function getFormat() {
      return $this->getRequest()->getParam('format');
    }

    public function getReturnFields() {
      return explode(',', $this->getRequest()->getParam('bx_returnfields'));
    }

    public function getScenario() {
      return 'parametrized';
    }

    public function getCmsRecommendationBlocks($content) {

      $recs = array();
      $recs[] = array(
        'widget'=>$this->getChoiceId(),
        'scenario'=>$this->getScenario(),
        'min'=>$this->getMin(),
        'max'=>$this->getMax()
      );
      $this->_data['widget'] = $this->getChoiceId();

      return $recs;
    }

}
