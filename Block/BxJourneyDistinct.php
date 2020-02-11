<?php
namespace Boxalino\Intelligence\Block;

/**
 * Class BxJourneyDivided
 * @package Boxalino\Intelligence\Block
 */
class BxJourneyDistinct extends BxJourneyAbstract
{

    protected function _construct()
    {
        $choices = $this->getChoices();
        if($this->bxHelperData->isPluginEnabled()) {
            $this->p13nHelper->setIsNarrative(true);
            $this->p13nHelper->setNarrativeChoices($choices);
        }
    }

    public function renderDependencies()
    {
        $html = '';
        if(!$this->bxHelperData->isPluginEnabled())
        {
            return $html;
        }

        $choices = $this->getChoices();
        foreach ($choices as $choice) {
            $dependencies = $this->p13nHelper->getNarrativeDependenciesResponse($choice);
            $html .= $this->buildHTMLByDependencies($dependencies);
        }

        return $html;
    }

    public function renderElements()
    {
        $html = '';
        if(!$this->bxHelperData->isPluginEnabled())
        {
            return $html;
        }

        $choices = $this->getChoices();
        foreach ($choices as $choice)
        {
            $narratives = $this->p13nHelper->getNarrativeResponse($choice);
            foreach ($narratives as $visualElement) {
                try {
                    $block = $this->createVisualElement($visualElement['visualElement']);
                    if ($block) {
                        $html .= $block->toHtml();
                    }
                } catch (\Exception $e) {
                    $this->_logger->critical($e);
                }
            }
        }

        return $html;
    }

    public function getChoices()
    {
        $additionalChoices = $this->getData("choices");
        foreach($additionalChoices as $name => $choice)
        {
            $default = $this->getDefaultParams();
            $default->addData($choice);
            $default->setName($name);

            if($default->getApplyContextParams())
            {
                $default = $this->applyContextParams($default);
            }

            unset($additionalChoices[$name]);
            $additionalChoices[$default->getVariant()] = $default;
        }

        ksort($additionalChoices);

        return $additionalChoices;
    }

    protected function applyContextParams($default)
    {
        $requestParams = $this->_request->getParams();
        $hitCount = $default->getHitCount();
        if(is_null($hitCount))
        {
            $hitCount = isset($requestParams['product_list_limit'])&&is_numeric($requestParams['product_list_limit']) ? $requestParams['product_list_limit'] : $this->p13nHelper->getMagentoStoreConfigPageSize();
        }

        $pageOffset = $default->getOffset();
        if(is_null($pageOffset)) {
            $pageOffset = isset($requestParams['p'])&&!empty($requestParams['p'])&&is_numeric($requestParams['p']) ? ($requestParams['p'] - 1) * ($hitCount) : 0;
        }

        $default->setOffset($pageOffset);
        $default->setHitCount($hitCount);

        return $default;
    }

    /**
     * Mock for a choice request
     *
     * @return Varien_Object
     */
    public function getDefaultParams()
    {
        $object =  $this->objectFactory->create();
        $object->setData([
                "name" => 'narrative',
                "with_facets"=> true,
                "hit_count" => NULL,
                "sort" => NULL,
                "order" => NULL,
                "offset" => NULL,
                "position" => 'main',
                "variant" => 0,
                "apply_context_params" => false,
                "context" => "products"
            ]
        );

        return $object;
    }

}
