<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
use Thrift\Exception\TProtocolException;
class Variant {
  static $_TSPEC;

  /**
   * @var string
   */
  public $variantId = null;
  /**
   * @var string
   */
  public $scenarioId = null;
  /**
   * @var \com\boxalino\p13n\api\thrift\SearchResult
   */
  public $searchResult = null;
  /**
   * @var string
   */
  public $searchResultTitle = null;
  /**
   * @var \com\boxalino\p13n\api\thrift\SearchRelaxation
   */
  public $searchRelaxation = null;
  /**
   * @var \com\boxalino\p13n\api\thrift\SearchResult[]
   */
  public $semanticFilteringResults = null;
  /**
   * @var array
   */
  public $extraInfo = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'variantId',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'scenarioId',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'searchResult',
          'type' => TType::STRUCT,
          'class' => '\com\boxalino\p13n\api\thrift\SearchResult',
          ),
        4 => array(
          'var' => 'searchResultTitle',
          'type' => TType::STRING,
          ),
        50 => array(
          'var' => 'searchRelaxation',
          'type' => TType::STRUCT,
          'class' => '\com\boxalino\p13n\api\thrift\SearchRelaxation',
          ),
        60 => array(
          'var' => 'semanticFilteringResults',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => '\com\boxalino\p13n\api\thrift\SearchResult',
            ),
          ),
        70 => array(
          'var' => 'extraInfo',
          'type' => TType::MAP,
          'ktype' => TType::STRING,
          'vtype' => TType::STRING,
          'key' => array(
            'type' => TType::STRING,
          ),
          'val' => array(
            'type' => TType::STRING,
            ),
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['variantId'])) {
        $this->variantId = $vals['variantId'];
      }
      if (isset($vals['scenarioId'])) {
        $this->scenarioId = $vals['scenarioId'];
      }
      if (isset($vals['searchResult'])) {
        $this->searchResult = $vals['searchResult'];
      }
      if (isset($vals['searchResultTitle'])) {
        $this->searchResultTitle = $vals['searchResultTitle'];
      }
      if (isset($vals['searchRelaxation'])) {
        $this->searchRelaxation = $vals['searchRelaxation'];
      }
      if (isset($vals['semanticFilteringResults'])) {
        $this->semanticFilteringResults = $vals['semanticFilteringResults'];
      }
      if (isset($vals['extraInfo'])) {
        $this->extraInfo = $vals['extraInfo'];
      }
    }
  }

  public function getName() {
    return 'Variant';
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
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->variantId);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->scenarioId);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRUCT) {
            $this->searchResult = new \com\boxalino\p13n\api\thrift\SearchResult();
            $xfer += $this->searchResult->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->searchResultTitle);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 50:
          if ($ftype == TType::STRUCT) {
            $this->searchRelaxation = new \com\boxalino\p13n\api\thrift\SearchRelaxation();
            $xfer += $this->searchRelaxation->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 60:
          if ($ftype == TType::LST) {
            $this->semanticFilteringResults = [];
            $_size185 = 0;
            $_etype188 = 0;
            $xfer += $input->readListBegin($_etype188, $_size185);
            for ($_i189 = 0; $_i189 < $_size185; ++$_i189)
            {
              $elem190 = null;
              $elem190 = new \com\boxalino\p13n\api\thrift\SearchResult();
              $xfer += $elem190->read($input);
              $this->semanticFilteringResults []= $elem190;
            }
            $xfer += $input->readListEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 70:
          if ($ftype == TType::MAP) {
            $this->extraInfo = [];
            $_size191 = 0;
            $_ktype192 = 0;
            $_vtype193 = 0;
            $xfer += $input->readMapBegin($_ktype192, $_vtype193, $_size191);
            for ($_i195 = 0; $_i195 < $_size191; ++$_i195)
            {
              $key196 = '';
              $val197 = '';
              $xfer += $input->readString($key196);
              $xfer += $input->readString($val197);
              $this->extraInfo[$key196] = $val197;
            }
            $xfer += $input->readMapEnd();
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
    $xfer += $output->writeStructBegin('Variant');
    if ($this->variantId !== null) {
      $xfer += $output->writeFieldBegin('variantId', TType::STRING, 1);
      $xfer += $output->writeString($this->variantId);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->scenarioId !== null) {
      $xfer += $output->writeFieldBegin('scenarioId', TType::STRING, 2);
      $xfer += $output->writeString($this->scenarioId);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->searchResult !== null) {
      if (!is_object($this->searchResult)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('searchResult', TType::STRUCT, 3);
      $xfer += $this->searchResult->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->searchResultTitle !== null) {
      $xfer += $output->writeFieldBegin('searchResultTitle', TType::STRING, 4);
      $xfer += $output->writeString($this->searchResultTitle);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->searchRelaxation !== null) {
      if (!is_object($this->searchRelaxation)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('searchRelaxation', TType::STRUCT, 50);
      $xfer += $this->searchRelaxation->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->semanticFilteringResults !== null) {
      if (!is_array($this->semanticFilteringResults)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('semanticFilteringResults', TType::LST, 60);
      {
        $output->writeListBegin(TType::STRUCT, count($this->semanticFilteringResults));
        {
          foreach ($this->semanticFilteringResults as $iter198)
          {
            $xfer += $iter198->write($output);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->extraInfo !== null) {
      if (!is_array($this->extraInfo)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('extraInfo', TType::MAP, 70);
      {
        $output->writeMapBegin(TType::STRING, TType::STRING, count($this->extraInfo));
        {
          foreach ($this->extraInfo as $kiter199 => $viter200)
          {
            $xfer += $output->writeString($kiter199);
            $xfer += $output->writeString($viter200);
          }
        }
        $output->writeMapEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}