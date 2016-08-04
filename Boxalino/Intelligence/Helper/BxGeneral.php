<?php

namespace Boxalino\Intelligence\Helper;
/**
 * Class BxGeneral
 * @package Boxalino\Intelligence\Helper
 */
class BxGeneral{
    
    /**
     * @param $string
     * @return string
     */
	public function escapeString($string){
        
        return htmlspecialchars(trim(preg_replace('/\s+/', ' ', $string)));
    }

    /**
     * Modifies a string to remove all non ASCII characters and spaces.
     */
    public function sanitizeFieldName($text){
        
        $maxLength = 50;
        $delimiter = "_";

        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', $delimiter, $text);

        // trim
        $text = trim($text, $delimiter);

        // transliterate
        if (function_exists('iconv')) {
            $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        }

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^_\w]+~', '', $text);

        if (empty($text)) {
            return null;
        }

        // max $maxLength (50) chars
        $text = substr($text, 0, $maxLength);
        $text = trim($text, $delimiter);
        return $text;
    }
}
