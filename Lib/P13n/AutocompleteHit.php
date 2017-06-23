<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
use Thrift\Exception\TProtocolException;
class AutocompleteHit {
  static $_TSPEC;

  /**
   * @var string
   */
  public $suggestion = null;
  /**
   * @var string
   */
  public $highlighted = null;
  /**
   * @var \com\boxalino\p13n\api\thrift\SearchResult
   */
  public $searchResult = null;
  /**
   * @var double
   */
  public $score = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        11 => array(
          'var' => 'suggestion',
          'type' => TType::STRING,
          ),
        21 => array(
          'var' => 'highlighted',
          'type' => TType::STRING,
          ),
        31 => array(
          'var' => 'searchResult',
          'type' => TType::STRUCT,
          'class' => '\com\boxalino\p13n\api\thrift\SearchResult',
          ),
        41 => array(
          'var' => 'score',
          'type' => TType::DOUBLE,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['suggestion'])) {
        $this->suggestion = $vals['suggestion'];
      }
      if (isset($vals['highlighted'])) {
        $this->highlighted = $vals['highlighted'];
      }
      if (isset($vals['searchResult'])) {
        $this->searchResult = $vals['searchResult'];
      }
      if (isset($vals['score'])) {
        $this->score = $vals['score'];
      }
    }
  }

  public function getName() {
    return 'AutocompleteHit';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 11:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->suggestion);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 21:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->highlighted);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 31:
          if ($ftype == TType::STRUCT) {
            $this->searchResult = new \com\boxalino\p13n\api\thrift\SearchResult();
            $xfer += $this->searchResult->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 41:
          if ($ftype == TType::DOUBLE) {
            $xfer += $input->readDouble($this->score);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('AutocompleteHit');
    if ($this->suggestion !== null) {
      $xfer += $output->writeFieldBegin('suggestion', TType::STRING, 11);
      $xfer += $output->writeString($this->suggestion);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->highlighted !== null) {
      $xfer += $output->writeFieldBegin('highlighted', TType::STRING, 21);
      $xfer += $output->writeString($this->highlighted);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->searchResult !== null) {
      if (!is_object($this->searchResult)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('searchResult', TType::STRUCT, 31);
      $xfer += $this->searchResult->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->score !== null) {
      $xfer += $output->writeFieldBegin('score', TType::DOUBLE, 41);
      $xfer += $output->writeDouble($this->score);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}