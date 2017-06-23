<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
use Thrift\Exception\TProtocolException;
class Filter {
  static $_TSPEC;

  /**
   * @var bool
   */
  public $negative = null;
  /**
   * @var string
   */
  public $fieldName = null;
  /**
   * @var string[]
   */
  public $stringValues = null;
  /**
   * @var string
   */
  public $prefix = null;
  /**
   * @var string
   */
  public $hierarchyId = null;
  /**
   * @var string[]
   */
  public $hierarchy = null;
  /**
   * @var string
   */
  public $rangeFrom = null;
  /**
   * @var bool
   */
  public $rangeFromInclusive = null;
  /**
   * @var string
   */
  public $rangeTo = null;
  /**
   * @var bool
   */
  public $rangeToInclusive = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'negative',
          'type' => TType::BOOL,
          ),
        2 => array(
          'var' => 'fieldName',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'stringValues',
          'type' => TType::LST,
          'etype' => TType::STRING,
          'elem' => array(
            'type' => TType::STRING,
            ),
          ),
        4 => array(
          'var' => 'prefix',
          'type' => TType::STRING,
          ),
        41 => array(
          'var' => 'hierarchyId',
          'type' => TType::STRING,
          ),
        5 => array(
          'var' => 'hierarchy',
          'type' => TType::LST,
          'etype' => TType::STRING,
          'elem' => array(
            'type' => TType::STRING,
            ),
          ),
        6 => array(
          'var' => 'rangeFrom',
          'type' => TType::STRING,
          ),
        7 => array(
          'var' => 'rangeFromInclusive',
          'type' => TType::BOOL,
          ),
        8 => array(
          'var' => 'rangeTo',
          'type' => TType::STRING,
          ),
        9 => array(
          'var' => 'rangeToInclusive',
          'type' => TType::BOOL,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['negative'])) {
        $this->negative = $vals['negative'];
      }
      if (isset($vals['fieldName'])) {
        $this->fieldName = $vals['fieldName'];
      }
      if (isset($vals['stringValues'])) {
        $this->stringValues = $vals['stringValues'];
      }
      if (isset($vals['prefix'])) {
        $this->prefix = $vals['prefix'];
      }
      if (isset($vals['hierarchyId'])) {
        $this->hierarchyId = $vals['hierarchyId'];
      }
      if (isset($vals['hierarchy'])) {
        $this->hierarchy = $vals['hierarchy'];
      }
      if (isset($vals['rangeFrom'])) {
        $this->rangeFrom = $vals['rangeFrom'];
      }
      if (isset($vals['rangeFromInclusive'])) {
        $this->rangeFromInclusive = $vals['rangeFromInclusive'];
      }
      if (isset($vals['rangeTo'])) {
        $this->rangeTo = $vals['rangeTo'];
      }
      if (isset($vals['rangeToInclusive'])) {
        $this->rangeToInclusive = $vals['rangeToInclusive'];
      }
    }
  }

  public function getName() {
    return 'Filter';
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
          if ($ftype == TType::BOOL) {
            $xfer += $input->readBool($this->negative);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->fieldName);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::LST) {
            $this->stringValues = array();
            $_size0 = 0;
            $_etype3 = 0;
            $xfer += $input->readListBegin($_etype3, $_size0);
            for ($_i4 = 0; $_i4 < $_size0; ++$_i4)
            {
              $elem5 = null;
              $xfer += $input->readString($elem5);
              $this->stringValues []= $elem5;
            }
            $xfer += $input->readListEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->prefix);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 41:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->hierarchyId);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 5:
          if ($ftype == TType::LST) {
            $this->hierarchy = array();
            $_size6 = 0;
            $_etype9 = 0;
            $xfer += $input->readListBegin($_etype9, $_size6);
            for ($_i10 = 0; $_i10 < $_size6; ++$_i10)
            {
              $elem11 = null;
              $xfer += $input->readString($elem11);
              $this->hierarchy []= $elem11;
            }
            $xfer += $input->readListEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 6:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->rangeFrom);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 7:
          if ($ftype == TType::BOOL) {
            $xfer += $input->readBool($this->rangeFromInclusive);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 8:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->rangeTo);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 9:
          if ($ftype == TType::BOOL) {
            $xfer += $input->readBool($this->rangeToInclusive);
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
    $xfer += $output->writeStructBegin('Filter');
    if ($this->negative !== null) {
      $xfer += $output->writeFieldBegin('negative', TType::BOOL, 1);
      $xfer += $output->writeBool($this->negative);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->fieldName !== null) {
      $xfer += $output->writeFieldBegin('fieldName', TType::STRING, 2);
      $xfer += $output->writeString($this->fieldName);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->stringValues !== null) {
      if (!is_array($this->stringValues)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('stringValues', TType::LST, 3);
      {
        $output->writeListBegin(TType::STRING, count($this->stringValues));
        {
          foreach ($this->stringValues as $iter12)
          {
            $xfer += $output->writeString($iter12);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->prefix !== null) {
      $xfer += $output->writeFieldBegin('prefix', TType::STRING, 4);
      $xfer += $output->writeString($this->prefix);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->hierarchy !== null) {
      if (!is_array($this->hierarchy)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('hierarchy', TType::LST, 5);
      {
        $output->writeListBegin(TType::STRING, count($this->hierarchy));
        {
          foreach ($this->hierarchy as $iter13)
          {
            $xfer += $output->writeString($iter13);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->rangeFrom !== null) {
      $xfer += $output->writeFieldBegin('rangeFrom', TType::STRING, 6);
      $xfer += $output->writeString($this->rangeFrom);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->rangeFromInclusive !== null) {
      $xfer += $output->writeFieldBegin('rangeFromInclusive', TType::BOOL, 7);
      $xfer += $output->writeBool($this->rangeFromInclusive);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->rangeTo !== null) {
      $xfer += $output->writeFieldBegin('rangeTo', TType::STRING, 8);
      $xfer += $output->writeString($this->rangeTo);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->rangeToInclusive !== null) {
      $xfer += $output->writeFieldBegin('rangeToInclusive', TType::BOOL, 9);
      $xfer += $output->writeBool($this->rangeToInclusive);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->hierarchyId !== null) {
      $xfer += $output->writeFieldBegin('hierarchyId', TType::STRING, 41);
      $xfer += $output->writeString($this->hierarchyId);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}