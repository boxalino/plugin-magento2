<?php

namespace Boxalino\Intelligence\Model;

class LayerFilterFilter {
	
	private $requestVar = null;
	
	public function setRequestVar($requestVar) {
		$this->requestVar = $requestVar;
	}
	
	public function getRequestVar() {
		return $this->requestVar;
	}

	private $cleanValue = null;
	
	public function setCleanValue($cleanValue) {
		$this->cleanValue = $cleanValue;
	}
	
	public function getCleanValue() {
		return $this->cleanValue;
	}

	private $clearLinkText = null;
	
	public function setClearLinkText($clearLinkText) {
		$this->clearLinkText = $clearLinkText;
	}
	
	public function getClearLinkText() {
		return $this->clearLinkText;
	}

	private $resetValue = null;
	
	public function setResetValue($resetValue) {
		$this->resetValue = $resetValue;
	}
	
	public function getResetValue() {
		return $this->resetValue;
	}
}
