<?php
namespace Boxalino\Intelligence\Helper;

/**
 * Class Autocomplete
 * @package Boxalino\Intelligence\Helper
 */
class Autocomplete{
	
	/**
	 * @var \Magento\Store\Model\StoreManagerInterface
	 */
	protected $_storeManager;

	/**
	 * Autocomplete constructor.
	 * @param \Magento\Store\Model\StoreManagerInterface $storeManager
	 */
	public function __construct(\Magento\Store\Model\StoreManagerInterface $storeManager){
		
		$this->_storeManager = $storeManager;
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
	public function getListValues($products) {
		
		$values = array();
		foreach($products as $product) {
			$mediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
			$value = array();
			$value['escape_name'] = $this->escapeHtml($product->getName());
			$value['name'] = $product->getName();
			$value['url'] = $product->getProductUrl();
			$value['price'] = strip_tags($product->getFormatedPrice());
			$value['image'] = $mediaUrl . "catalog/product" . $product->getImage();
			$values[$product->getId()] = $value;
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
