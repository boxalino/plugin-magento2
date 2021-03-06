<?php
\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Boxalino_Intelligence',
    __DIR__
);
if (defined('BP')) {
    $vendorDir = require BP . '/app/etc/vendor_path.php';
    $vendorAutoload = BP . "/{$vendorDir}/autoload.php";
    /** @var \Composer\Autoload\ClassLoader $composerAutoloader */
    $composerAutoloader = include $vendorAutoload;
    $composerAutoloader->addPsr4('com\\boxalino\\bxclient\\v1\\', array(__DIR__ . '/Lib'));
    $composerAutoloader->addPsr4('com\\boxalino\\p13n\\api\\thrift\\', array(__DIR__ . '/Lib/P13n'));
    $composerAutoloader->addPsr4('Thrift\\', array(__DIR__ . '/Lib/Thrift'));
}
