<?php
namespace Boxalino\Intelligence\Helper;

/**
 * Class Autocomplete
 * @package Boxalino\Intelligence\Helper
 */
class Autocomplete{
	
	/**
	 * @var \Magento\Catalog\Block\Product\AbstractProduct
	 */
	protected $abstractProduct;

	/**
	 * @var \Magento\Catalog\Model\Product
	 */
	protected $_criteriaBuilder;

	/**
	 * @var \Magento\Store\Model\StoreManagerInterface
	 */
	protected $storeManager;

	/**
	 * @var \Magento\Catalog\Api\ProductRepositoryInterface
	 */
	protected $productRepository;

	/**
	 * @var \Magento\Catalog\Helper\Image
	 */
	protected $_imageHelper;

	/**
	 * @var \Magento\Framework\Pricing\PriceCurrencyInterface
	 */
	protected $_priceCurrency;

	/**
	 * Autocomplete constructor.
	 * @param \Magento\Catalog\Block\Product\AbstractProduct $abstractProduct
	 * @param \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder
	 * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
	 * @param \Magento\Store\Model\StoreManagerInterface $storeManager
	 * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
	 * @param \Magento\Catalog\Helper\Image $imageHelper
	 */
	public function __construct(
		\Magento\Catalog\Block\Product\AbstractProduct $abstractProduct,
		\Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder,
		\Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
		\Magento\Catalog\Helper\Image $imageHelper
	)
	{
		$this->storeManager = $storeManager;
		$this->_criteriaBuilder = $criteriaBuilder;
		$this->abstractProduct = $abstractProduct;
		$this->_productRepository = $productRepository;
		$this->_imageHelper = $imageHelper;
		$this->_priceCurrency = $priceCurrency;
	}

	/**
	 * @param $string
	 * @return mixed
	 */
	public function escapeHtml($string) {
		
		return $string;
	}

	/**
	 * @param $products
	 * @return array
	 */
	public function getListValues($ids) {
		$values = [];
		$searchCriteria = $this->_criteriaBuilder->addFilter('entity_id', $ids, 'in')->create();
		$products       = $this->_productRepository->getList($searchCriteria);
		foreach($products->getItems() as $product){

			$image = $this->_imageHelper->init($product, 'product_page_image_small')->getUrl();
			$price = $product->getFinalPrice();

			if(($price == 0) && ($product->getTypeId() == 'grouped')) {
				$children = $product->getTypeInstance()->getAssociatedProducts($product);
				foreach ($children as $child) {
					if($child->getPrice() < $price || $price == 0) {
						$price = $child->getPrice();
					}
				}
			}

			$value = array();
			$value['escape_name'] = $this->escapeHtml($product->getName());
			$value['name'] = $product->getName();
			$value['url'] = $product->getProductUrl();
			$value['price'] = $this->_priceCurrency->format($price, false);
			$value['image'] = $image;
			$values[] = $value;
		}
		return $values;
	}

	/**
	 * @return string
	 */
	public function getProductACTemplate() {

		$template = '<a href="<%- data.product.url %>">';
		$template .= '<li class="<%- data.row_class %>" class="text_suggest_<%- data.suggestion %>" role="option">';
		$template .= '<span class="product-image"><img src="<%- data.product.image %>"></span>';
		$template .= '<span class="product-name"><%- data.product.name %></span>';
		$template .= '<span class="product-price"><%- data.product.price %></span>';
		$template .= '</li></a>';
		return $template;
	}
}
