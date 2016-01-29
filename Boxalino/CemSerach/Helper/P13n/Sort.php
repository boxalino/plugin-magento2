<?php

/**
 * User: Michal Sordyl
 * Mail: michal.sordyl@boxalino.com
 * Date: 02.06.14
 */
class Boxalino_CemSearch_Helper_P13n_Sort
{
    private $sorts = array();

    /**
     * @param array $sorts array(array('fieldName' => , 'reverse' => ), ..... )
     */
    public function __construct($sorts = array())
    {
        foreach ($sorts as $sort) {
            $this->push($sort['fieldName'], $sort['order']);
        }
    }

    /**
     * @param $field name od field to sort by (i.e. discountedPrice / title)
     * @param $reverse true for ASC, false for DESC
     */
    public function push($field, $reverse)
    {
        $this->sorts[] = array('fieldName' => $field, 'reverse' => $reverse);
    }

    public function getSorts()
    {
        return $this->sorts;
    }

}