<?php
namespace Boxalino\Frontend\Helper;

class Autocomplete
{
	public function escapeHtml($string) {
		return $string;
	}

	public function getListValues($products, $title) {
		$values = array();
		foreach($products as $product) {

			$value = array();
			$value['escape_name'] = $this->escapeHtml($product->getName());
			$value['name'] = $product->getName();
			$value['url'] = $product->getProductUrl();
			$value['price'] = strip_tags($product->getFormatedPrice());
			$value['image'] = "http" . (isset($_SERVER['HTTPS']) ? 's' : '') . "://" . $_SERVER['SERVER_NAME'] . "/magento/pub/media/catalog/product/" . $product->getImage();
			$value['suggestion'] = $title;
			preg_match("/<p>(.*?)<\/p>/s",$product->getDescription(),$match);
			$value['description'] = $match[1];
			$values[] = $value;
		}
		return $values;
	}

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
