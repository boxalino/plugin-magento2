<?php
namespace Boxalino\Intelligence\Block\Journey\Product;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Block\Product\ListProduct;
use \Boxalino\Intelligence\Block\Journey\CPOJourney as CPOJourney;
use Magento\Catalog\Block\Product\ProductList\Item\Block as ItemBlock;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\AwareInterface as ProductAwareInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Url\Helper\Data;

/**
 * Class ProductView
 *
 * Linked to GENERAL class for interface functions
 * @package Boxalino\Intelligence\Block\Journey\Product
 */
class ProductView extends ItemBlock implements
    ProductAwareInterface,
    CPOJourney
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Boxalino\Intelligence\Block\BxJourney
     */
    protected $bxJourney;

    /**
     * @var \Boxalino\Intelligence\Api\P13nAdapterInterface
     */
    protected $p13nHelper;

    /**
     * @var \Boxalino\Intelligence\Helper\ResourceManager
     */
    protected $bxResourceManager;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ProductInterface
     */
    private $product;

    /**
     * @var Data
     */
    protected $urlHelper;


    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Boxalino\Intelligence\Helper\ResourceManager $bxResourceManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \Boxalino\Intelligence\Block\BxJourney $journey,
        Data $urlHelper,
        array $data = []
    ){
        $this->urlHelper = $urlHelper;
        $this->objectManager = $objectManager;
        $this->bxResourceManager = $bxResourceManager;
        $this->p13nHelper = $p13nHelper;
        $this->bxJourney = $journey;
        $this->_logger = $context->getLogger();
        parent::__construct($context, $data);
    }


    /**
     * {@inheritdoc}
     */
    public function getProduct()
    {
        if(is_null($this->product)) {
            $this->product = $this->getBxProduct();
        }

        return $this->product;
    }

    /**
     * Loading the product based on the parent listing rendering logic
     *
     * @return bool|mixed|null
     */
    protected function getBxProduct()
    {
        $product = false;
        $index = $this->getCollectionId();
        $entityId = $this->getProductId();

        if($entityId) {
            $collection = $this->bxResourceManager->getResource($index, 'collection');
            if(!is_null($collection)) {
                foreach ($collection as $product) {
                    if($product->getId() == $entityId){
                        return $product;
                    }
                }
            }

            $product = $this->bxResourceManager->getResource($entityId, 'product');
            if(is_null($product)) {
                $product = $this->objectManager->create('Magento\Catalog\Model\Product')->load($entityId);
                $this->bxResourceManager->setResource($product, $entityId, 'product');
            }
        }

        return $product;
    }

    /**
     * Visual element collection for rendering items
     *
     * @return mixed
     */
    public function getCollectionId()
    {
        return $this->getData('bx_collection_id');
    }


    /**
     * The product ID is either defined via listing logic or product_id property on the visual element
     *
     * @return int|mixed
     */
    public function getProductId()
    {
        $id = $this->getData('bx_id');
        if(!$id)
        {
            $ids = $this->p13nHelper->getEntitiesIds($this->getCollectionId());
            $id = isset($ids[$this->getData("bx_index")]) ? $ids[$this->getData("bx_index")] : $this->getData("product_id");
        }

        return $id;
    }

    /**
     * Get post parameters
     *
     * @duplicate from Magento Core
     * @param Product $product
     * @return array
     */
    public function getAddToCartPostParams(Product $product)
    {
        $url = $this->getAddToCartUrl($product);
        return [
            'action' => $url,
            'data' => [
                'product' => $product->getEntityId(),
                ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlHelper->getEncodedUrl($url),
            ]
        ];
    }

    public function getLocalizedValue($values) {
        return $this->p13nHelper->getResponse()->getLocalizedValue($values);
    }

    public function getSubRenderings()
    {
        $elements = [];
        $element = $this->getData('bxVisualElement');
        if(isset($element['subRenderings'][0]['rendering']['visualElements'])) {
            $elements = $element['subRenderings'][0]['rendering']['visualElements'];
        }
        return $elements;
    }

    public function renderVisualElement($element, $additional_parameter = null)
    {
        return $this->bxJourney->createVisualElement($element, $additional_parameter)->toHtml();
    }

    public function getElementIndex() {
        return $this->getData('bx_index');
    }

    protected function _beforeToHtml()
    {
        return $this;
    }

}
