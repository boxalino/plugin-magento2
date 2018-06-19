<?php

namespace Boxalino\Intelligence\Block\Journey;

/**
 * Interface CPOJourney
 * @package Boxalino\Intelligence\Block\Journey
 */
interface CPOJourney{

    public function getSubRenderings();

    public function renderVisualElement($element, $additional_parameter = null);

    public function getLocalizedValue($values);
}
