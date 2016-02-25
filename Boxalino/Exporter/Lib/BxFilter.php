<?php

namespace com\boxalino\bxclient\v1;

class BxFilter
{
	protected $fieldName;
	protected $values;
	protected $negative;
	
	protected $hierarchyId = null;
	protected $hierarchy = null;
	protected $rangeFrom = null;
	protected $rangeTo = null;
	
	public function __construct($fieldName, $values=array(), $negative = false) {
		$this->fieldName = $fieldName;
		$this->values = $values;
		$this->negative = $negative;
	}
	
	public function getFieldName() {
		return $this->fieldName;
	}
	
	public function getValues() {
		return $this->values;
	}
	
	public function isNegative() {
		return $this->negative;
	}
	
	public function getHierarchyId() {
		return $this->hierarchyId;
	}
	
	public function setHierarchyId($hierarchyId) {
		$this->hierarchyId = $hierarchyId;
	}
	
	public function getHierarchy() {
		return $this->hierarchy;
	}
	
	public function setHierarchy($hierarchy) {
		$this->hierarchy = $hierarchy;
	}
	
	public function getRangeFrom() {
		return $this->rangeFrom;
	}
	
	public function setRangeFrom($rangeFrom) {
		$this->rangeFrom = $rangeFrom;
	}
	
	public function getRangeTo() {
		return $this->rangeTo;
	}
	
	public function setRangeTo($rangeTo) {
		$this->rangeTo = $rangeTo;
	}
	
	public function getThriftFilter() {
		$filter = new \com\boxalino\p13n\api\thrift\Filter();
        $filter->fieldName = $this->fieldName;
        $filter->negative = $this->negative;
        $filter->stringValues = $this->values;
		if($this->hierarchyId) {
			$filter->hierarchyId = $this->hierarchyId;
		}
		if($this->hierarchy) {
			$filter->hierarchy = $this->hierarchy;
		}
		if($this->rangeFrom) {
			$filter->rangeFrom = $this->rangeFrom;
		}
		if($this->rangeTo) {
			$filter->rangeTo = $this->rangeTo;
		}
        return $filter;
	}
}
