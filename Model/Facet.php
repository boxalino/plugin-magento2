<?php

namespace Boxalino\Intelligence\Model;

/**
 * Class Facet
 * @package Boxalino\Intelligence\Model
 */
class Facet {

    protected $bxFacets = array();

    public function setFacets($bxFacets) {
        $this->bxFacets = $bxFacets;
    }

    public function getFacets() {
        return $this->bxFacets;
    }
}
