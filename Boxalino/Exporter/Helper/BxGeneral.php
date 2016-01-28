<?php

namespace Boxalino\Exporter\Helper;

class BxGeneral
{
	public function escapeString($string)
    {
        return htmlspecialchars(trim(preg_replace('/\s+/', ' ', $string)));
    }
}
