<?php

namespace Boxalino\Intelligence\Model;
/**
 * Class LayerFilterFilter
 * @package Boxalino\Intelligence\Model
 */
class LayerFilterFilter {
	
	/**
	 * @var null
	 */
	private $requestVar = null;

	/**
	 * @var null
	 */
	private $clearLinkText = null;

	/**
	 * @var null
	 */
	private $cleanValue = null;

	/**
	 * @var null
	 */
	private $resetValue = null;
	
	/**
	 * @param $requestVar
	 */
	public function setRequestVar($requestVar) {
		
		$this->requestVar = $requestVar;
	}

	/**
	 * @return null
	 */
	public function getRequestVar() {
		
		return $this->requestVar;
	}
	
	/**
	 * @param $cleanValue
	 */
	public function setCleanValue($cleanValue) {
		
		$this->cleanValue = $cleanValue;
	}

	/**
	 * @return null
	 */
	public function getCleanValue() {
		
		return $this->cleanValue;
	}

	/**
	 * @param $clearLinkText
	 */
	public function setClearLinkText($clearLinkText) {
		
		$this->clearLinkText = $clearLinkText;
	}

	/**
	 * @return null
	 */
	public function getClearLinkText() {
		
		return $this->clearLinkText;
	}

	/**
	 * @param $resetValue
	 */
	public function setResetValue($resetValue) {
		
		$this->resetValue = $resetValue;
	}

	/**
	 * @return null
	 */
	public function getResetValue() {
		
		return $this->resetValue;
	}
}
