<?php
namespace Boxalino\Intelligence\Api;

use com\boxalino\bxclient\v1\BxSortFields;

/**
 * @package Boxalino\Intelligence\Api
 */
interface P13nAdapterInterface
{
    public function getSystemFilters($queryText = "", $type='product') : array;

    public function getSearchChoice($queryText, $isBlog = false);

    public function getFinderChoice() : string;

    public function getOverlayChoice() : string;

    public function getOverlayBannerChoice() : string;

    public function getProfileChoice() : string;

    public function getEntityIdFieldName() : string;

    public function getUrlParameterPrefix() : string;

    public function autocomplete($queryText, \Boxalino\Intelligence\Helper\Autocomplete $autocomplete);

    public function search($queryText, $pageOffset = 0, $hitCount,  \com\boxalino\bxclient\v1\BxSortFields $bxSortFields = null, $categoryId = null, $addFinder = false);

    public function getResponse();

    public function getRecommendation($widgetName, $context = array(), $widgetType = '', $minAmount = 3, $amount = 3, $execute = true, $returnFields = array());

    public function getTotalHitCount($variant_index = null);

    public function getEntitiesIds($variant_index = null);

    public function getFacets($getFinder = false);

    public function getCorrectedQuery();

    public function areThereSubPhrases();

    public function getQueryText() : string;

    public function prepareSortFields($requestParams, $orderBy=null, $direction=null) : BxSortFields;

    public function getNarratives($choice_id = 'narrative', $choices = null, $replaceMain = true, $execute = true);

    public function setNarrativeChoices($choiceConfiguration);

    public function addSingleNarrativeRequest($choice_id, $hitCount, $pageOffset, $orderBy=null, $direction=null, $withFacets = true);
}
