<?php

class BxRecommendation
{
	protected $account;
	protected $choiceId;
	protected $min;
	protected $max;
	
	public function __construct($account, $choiceId, $min, $max) {
		$this->account = $account;
		$this->choiceId = $choiceId;
		$this->min = (float)$min;
		$this->max = (float)$max;
		if($this->max === null) {
			$this->max = 5;
		}
	}
	
	public function getChoiceId() {
		return $this->choiceId;
	}
	
	public function getMax() {
		return $this->max;
	}

	public function getMin() {
		return $this->min;
	}

	public function getAccount() {
		return $this->account;
	}
	
	protected $contextItems = array();
	public function setProductContext($fieldName, $contextItemId, $role = 'mainProduct') {
		$contextItem = new \com\boxalino\p13n\api\thrift\ContextItem();
		$contextItem->indexId = $this->getAccount();
		$contextItem->fieldName = $fieldName;
		$contextItem->contextItemId = $contextItemId;
		$contextItem->role = $role;
		$this->contextItems[] = $contextItem;
	}
	
	public function setBasketContext($fieldName, $basketContent, $role = 'mainProduct') {
		if ($basketContent !== false && count($basketContent)) {
			
			// Sort basket content by price
			usort($basketContent, function ($a, $b) {
				if ($a['price'] > $b['price']) {
					return -1;
				} elseif ($b['price'] > $a['price']) {
					return 1;
				}
				return 0;
			});

			$basketItem = array_shift($basketContent);

			$contextItem = new \com\boxalino\p13n\api\thrift\ContextItem();
			$contextItem->indexId = $this->getAccount();
			$contextItem->fieldName = $fieldName;
			$contextItem->contextItemId = $basketItem['id'];
			$contextItem->role = $role;

			$this->contextItems[] = $contextItem;

			foreach ($basketContent as $basketItem) {
				$contextItem = new \com\boxalino\p13n\api\thrift\ContextItem();
				$contextItem->indexId = $this->getAccount();
				$contextItem->fieldName = $fieldName;
				$contextItem->contextItemId = $basketItem['id'];
				$contextItem->role = $role;

				$this->contextItems[] = $contextItem;
			}
		}
	}
	
	public function getContextItems() {
		return $this->contextItems;
	}

}
