<?php

class BxFilter
{
	protected $fieldName;
	protected $values;
	protected $negative;
	
	public function __construct($fieldName, $values, $negative = false) {
		$this->fieldName = $fieldName;
		$this->values = $values;
		$this->negative = $negative;
	}
	
	public function getThriftFilter() {
		$filter = new \com\boxalino\p13n\api\thrift\Filter();
        $filter->fieldName = $this->fieldName;
        $filter->negative = $this->negative;
        $filter->stringValues = $this->values;
        return $filter;
	}
}
