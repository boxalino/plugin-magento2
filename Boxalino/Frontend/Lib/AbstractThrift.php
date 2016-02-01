<?php

abstract class AbstractThrift
{
    protected static $thriftClassLoader = null;

    protected $dependencies = array();

    public function __construct()
    {
        if (self::$thriftClassLoader === null) {
            $this->initClassLoader();
        }
    }

    protected function initClassLoader()
    {
        require_once(__DIR__ . '/vendor/Thrift/ClassLoader/ThriftClassLoader.php');
        self::$thriftClassLoader = new \Thrift\ClassLoader\ThriftClassLoader(false);
        self::$thriftClassLoader->registerNamespace('Thrift', __DIR__ . '/vendor');
        self::$thriftClassLoader->register();
        foreach ($this->dependencies as $dependency) {
            require_once($dependency);
        }
    }

    abstract protected function getClient($clientId = '');
}
