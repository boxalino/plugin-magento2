<?php

namespace com\boxalino\bxclient\v1;

class BxSortFields
{
	private $sorts = array();

    public function __construct($field=null, $reverse=false)
    {
		if($field) {
			$this->push($field, $reverse);
		}
    }

    /**
     * @param $field name od field to sort by (i.e. discountedPrice / title)
     * @param $reverse true for ASC, false for DESC
     */
    public function push($field, $reverse=false)
    {
        $this->sorts[$field] = $reverse;
    }

    public function getSortFields()
    {
		return array_keys($this->sorts);
    }
	
	public function isFieldReverse($field) {
		if(isset($this->sorts[$field]) && $this->sorts[$field]) {
			return true;
		}
		return false;
	}
	
	public function getThriftSortFields() {
		$sortFields = array();
		foreach ($this->getSortFields() as $field) {
			$sortFields[] = new \com\boxalino\p13n\api\thrift\SortField(array(
				'fieldName' => $field,
				'reverse' => $this->isFieldReverse($field)
			));
		}
		return $sortFields;
	}
}
