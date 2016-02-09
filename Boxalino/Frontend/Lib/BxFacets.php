<?php

class BxFacets
{
	protected $facets = array();
	protected $requestFacets;
	
	public function __construct($requestFacets) {
		$this->requestFacets = $requestFacets;
	}
	
	public function addFacet($fieldName, $type, $direction=2) {
		$this->facets[] = array($fieldName, $type, $direction);
	}
	
	public function getThriftFacets() {
		
		$thriftFacets = array();
		
		foreach($this->facets as $facet) {
			$facetRequest = new \com\boxalino\p13n\api\thrift\FacetRequest();
			$facetRequest->fieldName = $facet[0];
			$facetRequest->numerical = $facet[1] == 'ranged' ? true : $facet[1] == 'numerical' ? true : false;
			$facetRequest->range = $facet[1] == 'ranged' ? true : false;
			$facetRequest->selectedValues = $this->facetSelectedValue($facet[0], $facet[1]);
			$facetRequest->sortOrder = isset($facet[2]) && $facet[2] == 1 ? 1 : 2;
			$thriftFacets[] = $facetRequest;
		}
		
		return $thriftFacets;
	}

    private function facetSelectedValue($name, $option)
    {
        $selectedFacets = array();
        if (isset($this->requestFacets[$name])) {
            foreach ($this->requestFacets[$name] as $value) {
                $selectedFacet = new \com\boxalino\p13n\api\thrift\FacetValue();
                if ($option == 'ranged') {
                    $rangedValue = explode('-', $value);
                    if ($rangedValue[0] != '*') {
                        $selectedFacet->rangeFromInclusive = $rangedValue[0];
                    }
                    if ($rangedValue[1] != '*') {
                        $selectedFacet->rangeToExclusive = $rangedValue[1];
                    }
                } else {
                    $selectedFacet->stringValue = $value;
                }
                $selectedFacets[] = $selectedFacet;

            }
            return $selectedFacets;
        }
        return;
    }
}
