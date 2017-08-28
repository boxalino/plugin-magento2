<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
use Thrift\Exception\TProtocolException;
class SearchRelaxation {
  static $_TSPEC;

  /**
   * @var \com\boxalino\p13n\api\thrift\SearchResult[]
   */
  public $suggestionsResults = null;
  /**
   * @var \com\boxalino\p13n\api\thrift\SearchResult[]
   */
  public $subphrasesResults = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        10 => array(
          'var' => 'suggestionsResults',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => '\com\boxalino\p13n\api\thrift\SearchResult',
            ),
          ),
        20 => array(
          'var' => 'subphrasesResults',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => '\com\boxalino\p13n\api\thrift\SearchResult',
            ),
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['suggestionsResults'])) {
        $this->suggestionsResults = $vals['suggestionsResults'];
      }
      if (isset($vals['subphrasesResults'])) {
        $this->subphrasesResults = $vals['subphrasesResults'];
      }
    }
  }

  public function getName() {
    return 'SearchRelaxation';
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
        case 10:
          if ($ftype == TType::LST) {
            $this->suggestionsResults = array();
            $_size171 = 0;
            $_etype174 = 0;
            $xfer += $input->readListBegin($_etype174, $_size171);
            for ($_i175 = 0; $_i175 < $_size171; ++$_i175)
            {
              $elem176 = null;
              $elem176 = new \com\boxalino\p13n\api\thrift\SearchResult();
              $xfer += $elem176->read($input);
              $this->suggestionsResults []= $elem176;
            }
            $xfer += $input->readListEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 20:
          if ($ftype == TType::LST) {
            $this->subphrasesResults = array();
            $_size177 = 0;
            $_etype180 = 0;
            $xfer += $input->readListBegin($_etype180, $_size177);
            for ($_i181 = 0; $_i181 < $_size177; ++$_i181)
            {
              $elem182 = null;
              $elem182 = new \com\boxalino\p13n\api\thrift\SearchResult();
              $xfer += $elem182->read($input);
              $this->subphrasesResults []= $elem182;
            }
            $xfer += $input->readListEnd();
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
    $xfer += $output->writeStructBegin('SearchRelaxation');
    if ($this->suggestionsResults !== null) {
      if (!is_array($this->suggestionsResults)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('suggestionsResults', TType::LST, 10);
      {
        $output->writeListBegin(TType::STRUCT, count($this->suggestionsResults));
        {
          foreach ($this->suggestionsResults as $iter183)
          {
            $xfer += $iter183->write($output);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->subphrasesResults !== null) {
      if (!is_array($this->subphrasesResults)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('subphrasesResults', TType::LST, 20);
      {
        $output->writeListBegin(TType::STRUCT, count($this->subphrasesResults));
        {
          foreach ($this->subphrasesResults as $iter184)
          {
            $xfer += $iter184->write($output);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}