<?php
namespace Boxalino\Frontend\Helper;

class Autocomplete
{
	public function escapeHtml($string) {
		return $string;
	}
	
    public function getListHtml($products) {
        $html = '<ul class="products">';
		$first = true;
		foreach($products as $product) {
			$hash = "asdf";//todo
			if($first)  {
				$html .= '<li data-word="' . $hash . '" class="product-autocomplete" title="' . $this->escapeHtml($product->getName()) . '">';
			} else {
				$html .= '<li style="display:none" data-word="' . $hash . '" class="product-autocomplete" title="' . $this->escapeHtml($product->getName()) . '">';
			}
			$first = false;
			$html .= '<a href="' . $product->getProductUrl() . '" >';
			$html .= '<div class="product-image"><img src="' . $product->getThumbnailUrl() . '" alt="' . $product->getName() . '"></div>';
			$html .= '<div class="product-title"><span>' . $product->getName() . '</span></div>';
			$html .= '</a>';
			$html .= '</li>';
		}
		$html .= '</ul>';
		return $html;
	}
	
}
