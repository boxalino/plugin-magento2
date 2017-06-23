<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
use Thrift\Exception\TProtocolException;
class FacetResponse {
  static $_TSPEC;

  /**
   * @var string
   */
  public $fieldName = null;
  /**
   * @var \com\boxalino\p13n\api\thrift\FacetValue[]
   */
  public $values = null;
  /**
   * @var bool
   */
  public $evaluate = null;
  /**
   * @var string
   */
  public $display = null;
  /**
   * @var bool
   */
  public $numerical = null;
  /**
   * @var bool
   */
  public $range = null;
  /**
   * @var int
   */
  public $sortOrder = null;
  /**
   * @var bool
   */
  public $sortAscending = null;
  /**
   * @var bool
   */
  public $andSelectedValues = null;
  /**
   * @var bool
   */
  public $boundsOnly = null;
  /**
   * @var array
   */
  public $extraInfo = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'fieldName',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'values',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => '\com\boxalino\p13n\api\thrift\FacetValue',
            ),
          ),
        3 => array(
          'var' => 'evaluate',
          'type' => TType::BOOL,
          ),
        4 => array(
          'var' => 'display',
          'type' => TType::STRING,
          ),
        5 => array(
          'var' => 'numerical',
          'type' => TType::BOOL,
          ),
        6 => array(
          'var' => 'range',
          'type' => TType::BOOL,
          ),
        7 => array(
          'var' => 'sortOrder',
          'type' => TType::I32,
          ),
        8 => array(
          'var' => 'sortAscending',
          'type' => TType::BOOL,
          ),
        9 => array(
          'var' => 'andSelectedValues',
          'type' => TType::BOOL,
          ),
        10 => array(
          'var' => 'boundsOnly',
          'type' => TType::BOOL,
          ),
        11 => array(
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
      if (isset($vals['fieldName'])) {
        $this->fieldName = $vals['fieldName'];
      }
      if (isset($vals['values'])) {
        $this->values = $vals['values'];
      }
      if (isset($vals['evaluate'])) {
        $this->evaluate = $vals['evaluate'];
      }
      if (isset($vals['display'])) {
        $this->display = $vals['display'];
      }
      if (isset($vals['numerical'])) {
        $this->numerical = $vals['numerical'];
      }
      if (isset($vals['range'])) {
        $this->range = $vals['range'];
      }
      if (isset($vals['sortOrder'])) {
        $this->sortOrder = $vals['sortOrder'];
      }
      if (isset($vals['sortAscending'])) {
        $this->sortAscending = $vals['sortAscending'];
      }
      if (isset($vals['andSelectedValues'])) {
        $this->andSelectedValues = $vals['andSelectedValues'];
      }
      if (isset($vals['boundsOnly'])) {
        $this->boundsOnly = $vals['boundsOnly'];
      }
      if (isset($vals['extraInfo'])) {
        $this->extraInfo = $vals['extraInfo'];
      }
    }
  }

  public function getName() {
    return 'FacetResponse';
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
            $xfer += $input->readString($this->fieldName);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::LST) {
            $this->values = array();
            $_size111 = 0;
            $_etype114 = 0;
            $xfer += $input->readListBegin($_etype114, $_size111);
            for ($_i115 = 0; $_i115 < $_size111; ++$_i115)
            {
              $elem116 = null;
              $elem116 = new \com\boxalino\p13n\api\thrift\FacetValue();
              $xfer += $elem116->read($input);
              $this->values []= $elem116;
            }
            $xfer += $input->readListEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::BOOL) {
            $xfer += $input->readBool($this->evaluate);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->display);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 5:
          if ($ftype == TType::BOOL) {
            $xfer += $input->readBool($this->numerical);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 6:
          if ($ftype == TType::BOOL) {
            $xfer += $input->readBool($this->range);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 7:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->sortOrder);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 8:
          if ($ftype == TType::BOOL) {
            $xfer += $input->readBool($this->sortAscending);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 9:
          if ($ftype == TType::BOOL) {
            $xfer += $input->readBool($this->andSelectedValues);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 10:
          if ($ftype == TType::BOOL) {
            $xfer += $input->readBool($this->boundsOnly);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 11:
          if ($ftype == TType::MAP) {
            $this->extraInfo = array();
            $_size117 = 0;
            $_ktype118 = 0;
            $_vtype119 = 0;
            $xfer += $input->readMapBegin($_ktype118, $_vtype119, $_size117);
            for ($_i121 = 0; $_i121 < $_size117; ++$_i121)
            {
              $key122 = '';
              $val123 = '';
              $xfer += $input->readString($key122);
              $xfer += $input->readString($val123);
              $this->extraInfo[$key122] = $val123;
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
    $xfer += $output->writeStructBegin('FacetResponse');
    if ($this->fieldName !== null) {
      $xfer += $output->writeFieldBegin('fieldName', TType::STRING, 1);
      $xfer += $output->writeString($this->fieldName);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->values !== null) {
      if (!is_array($this->values)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('values', TType::LST, 2);
      {
        $output->writeListBegin(TType::STRUCT, count($this->values));
        {
          foreach ($this->values as $iter124)
          {
            $xfer += $iter124->write($output);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->evaluate !== null) {
      $xfer += $output->writeFieldBegin('evaluate', TType::BOOL, 3);
      $xfer += $output->writeBool($this->evaluate);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->display !== null) {
      $xfer += $output->writeFieldBegin('display', TType::STRING, 4);
      $xfer += $output->writeString($this->display);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->numerical !== null) {
      $xfer += $output->writeFieldBegin('numerical', TType::BOOL, 5);
      $xfer += $output->writeBool($this->numerical);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->range !== null) {
      $xfer += $output->writeFieldBegin('range', TType::BOOL, 6);
      $xfer += $output->writeBool($this->range);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->sortOrder !== null) {
      $xfer += $output->writeFieldBegin('sortOrder', TType::I32, 7);
      $xfer += $output->writeI32($this->sortOrder);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->sortAscending !== null) {
      $xfer += $output->writeFieldBegin('sortAscending', TType::BOOL, 8);
      $xfer += $output->writeBool($this->sortAscending);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->andSelectedValues !== null) {
      $xfer += $output->writeFieldBegin('andSelectedValues', TType::BOOL, 9);
      $xfer += $output->writeBool($this->andSelectedValues);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->boundsOnly !== null) {
      $xfer += $output->writeFieldBegin('boundsOnly', TType::BOOL, 10);
      $xfer += $output->writeBool($this->boundsOnly);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->extraInfo !== null) {
      if (!is_array($this->extraInfo)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('extraInfo', TType::MAP, 11);
      {
        $output->writeMapBegin(TType::STRING, TType::STRING, count($this->extraInfo));
        {
          foreach ($this->extraInfo as $kiter125 => $viter126)
          {
            $xfer += $output->writeString($kiter125);
            $xfer += $output->writeString($viter126);
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