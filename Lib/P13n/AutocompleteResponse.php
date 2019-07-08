<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
use Thrift\Exception\TProtocolException;
class AutocompleteResponse {
  static $_TSPEC;

  /**
   * @var \com\boxalino\p13n\api\thrift\AutocompleteHit[]
   */
  public $hits = null;
  /**
   * @var \com\boxalino\p13n\api\thrift\SearchResult
   */
  public $prefixSearchResult = null;
  /**
   * @var \com\boxalino\p13n\api\thrift\PropertyResult[]
   */
  public $propertyResults = null;
  /**
   * @var array
   */
  public $extraInfo = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        11 => array(
          'var' => 'hits',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => '\com\boxalino\p13n\api\thrift\AutocompleteHit',
            ),
          ),
        21 => array(
          'var' => 'prefixSearchResult',
          'type' => TType::STRUCT,
          'class' => '\com\boxalino\p13n\api\thrift\SearchResult',
          ),
        31 => array(
          'var' => 'propertyResults',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => '\com\boxalino\p13n\api\thrift\PropertyResult',
            ),
          ),
        41 => array(
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
      if (isset($vals['hits'])) {
        $this->hits = $vals['hits'];
      }
      if (isset($vals['prefixSearchResult'])) {
        $this->prefixSearchResult = $vals['prefixSearchResult'];
      }
      if (isset($vals['propertyResults'])) {
        $this->propertyResults = $vals['propertyResults'];
      }
      if (isset($vals['extraInfo'])) {
        $this->extraInfo = $vals['extraInfo'];
      }
    }
  }

  public function getName() {
    return 'AutocompleteResponse';
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
          if ($ftype == TType::LST) {
            $this->hits = [];
            $_size280 = 0;
            $_etype283 = 0;
            $xfer += $input->readListBegin($_etype283, $_size280);
            for ($_i284 = 0; $_i284 < $_size280; ++$_i284)
            {
              $elem285 = null;
              $elem285 = new \com\boxalino\p13n\api\thrift\AutocompleteHit();
              $xfer += $elem285->read($input);
              $this->hits []= $elem285;
            }
            $xfer += $input->readListEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 21:
          if ($ftype == TType::STRUCT) {
            $this->prefixSearchResult = new \com\boxalino\p13n\api\thrift\SearchResult();
            $xfer += $this->prefixSearchResult->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 31:
          if ($ftype == TType::LST) {
            $this->propertyResults = array();
            $_size286 = 0;
            $_etype289 = 0;
            $xfer += $input->readListBegin($_etype289, $_size286);
            for ($_i290 = 0; $_i290 < $_size286; ++$_i290)
            {
              $elem291 = null;
              $elem291 = new \com\boxalino\p13n\api\thrift\PropertyResult();
              $xfer += $elem291->read($input);
              $this->propertyResults []= $elem291;
            }
            $xfer += $input->readListEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 41:
          if ($ftype == TType::MAP) {
            $this->extraInfo = array();
            $_size292 = 0;
            $_ktype293 = 0;
            $_vtype294 = 0;
            $xfer += $input->readMapBegin($_ktype293, $_vtype294, $_size292);
            for ($_i296 = 0; $_i296 < $_size292; ++$_i296)
            {
              $key297 = '';
              $val298 = '';
              $xfer += $input->readString($key297);
              $xfer += $input->readString($val298);
              $this->extraInfo[$key297] = $val298;
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
    $xfer += $output->writeStructBegin('AutocompleteResponse');
    if ($this->hits !== null) {
      if (!is_array($this->hits)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('hits', TType::LST, 11);
      {
        $output->writeListBegin(TType::STRUCT, count($this->hits));
        {
          foreach ($this->hits as $iter299)
          {
            $xfer += $iter299->write($output);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->prefixSearchResult !== null) {
      if (!is_object($this->prefixSearchResult)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('prefixSearchResult', TType::STRUCT, 21);
      $xfer += $this->prefixSearchResult->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->propertyResults !== null) {
      if (!is_array($this->propertyResults)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('propertyResults', TType::LST, 31);
      {
        $output->writeListBegin(TType::STRUCT, count($this->propertyResults));
        {
          foreach ($this->propertyResults as $iter300)
          {
            $xfer += $iter300->write($output);
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
      $xfer += $output->writeFieldBegin('extraInfo', TType::MAP, 41);
      {
        $output->writeMapBegin(TType::STRING, TType::STRING, count($this->extraInfo));
        {
          foreach ($this->extraInfo as $kiter301 => $viter302)
          {
            $xfer += $output->writeString($kiter301);
            $xfer += $output->writeString($viter302);
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