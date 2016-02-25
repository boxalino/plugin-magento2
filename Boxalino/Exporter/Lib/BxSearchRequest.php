<?php

namespace com\boxalino\bxclient\v1;

class BxSearchRequest extends BxRequest
{
	public function __construct($language, $queryText, $max=10, $choiceId=null) {
		if($choiceId == null) {
			$choiceId = 'search';
		}
		parent::__construct($language, $choiceId, $max, 0);
		$this->setQueryText($queryText);
	}
}
