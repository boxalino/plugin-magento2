<?php
namespace Boxalino\Intelligence\Block;

/**
 * Class BxJourney
 * @package Boxalino\Intelligence\Block
 */
class BxJourney extends BxJourneyAbstract
{

    protected function _construct()
    {
        if(!is_null($this->getData('choice')) && $this->bxHelperData->isPluginEnabled()) {
            $replaceMain = is_null($this->getData('replace_main')) ? true : $this->getData('replace_main');
            $this->p13nHelper->getNarratives($this->getData('choice'), $this->getData('additional_choices'), $replaceMain, false);
        }
    }

    public function renderDependencies() {
        $html = '';
        if(!$this->bxHelperData->isPluginEnabled())
        {
            return $html;
        }
        $replaceMain = is_null($this->getData('replace_main')) ? true : $this->getData('replace_main');
        $dependencies = $this->p13nHelper->getNarrativeDependencies($this->getData('choice'), $this->getData('additional_choices'), $replaceMain);

        return $this->buildHTMLByDependencies($dependencies);
    }

    public function renderElements() {

        $html = '';
        if(!$this->bxHelperData->isPluginEnabled())
        {
            return $html;
        }
        $position = $this->getData('position');
        $replaceMain = is_null($this->getData('replace_main')) ? true : $this->getData('replace_main');
        $narratives = $this->p13nHelper->getNarratives($this->getData('choice'), $this->getData('additional_choices'), $replaceMain);

        foreach ($narratives as $visualElement) {
            if($this->checkVisualElementForParameter($visualElement['visualElement'], 'position', $position)) {
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
}
