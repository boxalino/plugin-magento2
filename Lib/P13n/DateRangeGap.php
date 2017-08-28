<?php

namespace com\boxalino\p13n\api\thrift;

final class DateRangeGap {
  const SECOND = 1;
  const MINUTE = 2;
  const HOUR = 3;
  const DAY = 4;
  const WEEK = 5;
  const MONTH = 6;
  const YEAR = 7;
  const DECADE = 8;
  const CENTURY = 9;
  static public $__names = array(
    1 => 'SECOND',
    2 => 'MINUTE',
    3 => 'HOUR',
    4 => 'DAY',
    5 => 'WEEK',
    6 => 'MONTH',
    7 => 'YEAR',
    8 => 'DECADE',
    9 => 'CENTURY',
  );
}