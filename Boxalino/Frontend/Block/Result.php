<?php
namespace Boxalino\Frontend\Block;
use Magento\CatalogSearch\Block\Result as Mage_Result;
use Boxalino\Frontend\Helper\Data as BxData;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\CatalogSearch\Helper\Data;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Search\Model\QueryFactory;
class Result extends Mage_Result
{

    protected $bxHelperData;

    public function __construct(
        Context $context,
        LayerResolver $layerResolver,
        Data $catalogSearchData,
        QueryFactory $queryFactory,
        BxData $bxHelperData,
        array $data = [])
    {
        $this->bxHelperData = $bxHelperData;
        parent::__construct($context, $layerResolver, $catalogSearchData, $queryFactory, $data);
    }

    /**
     * Retrieve search result count
     *
     * @return string
     */
    public function getResultCount()
    {
        if (!$this->getData('result_count')) {
            $size = $this->bxHelperData->getSearchAdapter()->getTotalHitCount();
            $this->_getQuery()->setNumResults($size);
            $this->setResultCount($size);
        }
        return $this->getData('result_count');
    }
}