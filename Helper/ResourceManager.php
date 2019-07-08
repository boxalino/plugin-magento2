<?php

namespace Boxalino\Intelligence\Helper;
/**
 * Class ResourceManager
 * @package Boxalino\Intelligence\Helper
 */
class ResourceManager{

    /**
     * @var array
     */
    protected $resource = [];

    /**
     * @var array
     */
    protected $types = array('collection', 'product');

    public function __construct()
    {
        $this->initResource();
    }

    protected function initResource() {
        foreach ($this->types as $type) {
            $this->resource[$type] = [];
        }
    }

    public function getResource($id, $type) {

        $resource = null;

        if(isset($this->resource[$type]) && isset($this->resource[$type][$id])) {
            $resource = $this->resource[$type][$id];
        }
        return $resource;
    }

    public function setResource($resource, $id, $type) {
        if(!isset($this->resource[$type])) {
            $this->resource[$type] = [];
        }
        $this->resource[$type][$id] = $resource;
    }
}
