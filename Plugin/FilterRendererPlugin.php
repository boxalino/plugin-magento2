<?php
namespace Boxalino\Intelligence\Plugin;
use \Boxalino\Intelligence\Model\Attribute;
class FilterRendererPlugin{

    public function beforeRender($subject, $filter) {

        if ($filter instanceof Attribute) {
            $subject->assign('bxFacets', $filter->getBxFacets());
            $subject->assign('bxFieldName', $filter->getFieldName());
        }
    }
}
