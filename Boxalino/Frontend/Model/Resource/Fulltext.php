<?php
namespace Boxalino\Frontend\Model\Resource;

class Fulltext extends \Magento\CatalogSearch\Model\ResourceModel\Fulltext
{

    private function cmp($a, $b)
    {
        if ($a['hits'] == $b['hits']) {
            return 0;
        }
        return ($a['hits'] > $b['hits']) ? -1 : 1;
    }
}
