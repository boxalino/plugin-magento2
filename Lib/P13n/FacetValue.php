<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
use Thrift\Exception\TProtocolException;
class FacetValue {
  static $_TSPEC;

  /**
   * @var string
   */
  public $stringValue = null;
  /**
   * @var string
   */
  public $rangeFromInclusive = null;
  /**
   * @var string
   */
  public $rangeToExclusive = null;
  /**
   * @var int
   */
  public $hitCount = null;
  /**
   * @var string
   */
  public $hierarchyId = null;
  /**
   * @var string[]
   */
  public $hierarchy = null;
  /**
   * @var bool
   */
  public $selected = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'stringValue',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'rangeFromInclusive',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'rangeToExclusive',
          'type' => TType::STRING,
          ),
        4 => array(
          'var' => 'hitCount',
          'type' => TType::I64,
          ),
        50 => array(
          'var' => 'hierarchyId',
          'type' => TType::STRING,
          ),
        60 => array(
          'var' => 'hierarchy',
          'type' => TType::LST,
          'etype' => TType::STRING,
          'elem' => array(
            'type' => TType::STRING,
            ),
          ),
        70 => array(
          'var' => 'selected',
          'type' => TType::BOOL,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['stringValue'])) {
        $this->stringValue = $vals['stringValue'];
      }
      if (isset($vals['rangeFromInclusive'])) {
        $this->rangeFromInclusive = $vals['rangeFromInclusive'];
      }
      if (isset($vals['rangeToExclusive'])) {
        $this->rangeToExclusive = $vals['rangeToExclusive'];
      }
      if (isset($vals['hitCount'])) {
        $this->hitCount = $vals['hitCount'];
      }
      if (isset($vals['hierarchyId'])) {
        $this->hierarchyId = $vals['hierarchyId'];
      }
      if (isset($vals['hierarchy'])) {
        $this->hierarchy = $vals['hierarchy'];
      }
      if (isset($vals['selected'])) {
        $this->selected = $vals['selected'];
      }
    }
  }

  public function getName() {
    return 'FacetValue';
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
            $xfer += $input->readString($this->stringValue);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->rangeFromInclusive);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->rangeToExclusive);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::I64) {
            $xfer += $input->readI64($this->hitCount);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 50:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->hierarchyId);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 60:
          if ($ftype == TType::LST) {
            $this->hierarchy = array();
            $_size14 = 0;
            $_etype17 = 0;
            $xfer += $input->readListBegin($_etype17, $_size14);
            for ($_i18 = 0; $_i18 < $_size14; ++$_i18)
            {
              $elem19 = null;
              $xfer += $input->readString($elem19);
              $this->hierarchy []= $elem19;
            }
            $xfer += $input->readListEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 70:
          if ($ftype == TType::BOOL) {
            $xfer += $input->readBool($this->selected);
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
    $xfer += $output->writeStructBegin('FacetValue');
    if ($this->stringValue !== null) {
      $xfer += $output->writeFieldBegin('stringValue', TType::STRING, 1);
      $xfer += $output->writeString($this->stringValue);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->rangeFromInclusive !== null) {
      $xfer += $output->writeFieldBegin('rangeFromInclusive', TType::STRING, 2);
      $xfer += $output->writeString($this->rangeFromInclusive);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->rangeToExclusive !== null) {
      $xfer += $output->writeFieldBegin('rangeToExclusive', TType::STRING, 3);
      $xfer += $output->writeString($this->rangeToExclusive);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->hitCount !== null) {
      $xfer += $output->writeFieldBegin('hitCount', TType::I64, 4);
      $xfer += $output->writeI64($this->hitCount);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->hierarchyId !== null) {
      $xfer += $output->writeFieldBegin('hierarchyId', TType::STRING, 50);
      $xfer += $output->writeString($this->hierarchyId);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->hierarchy !== null) {
      if (!is_array($this->hierarchy)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('hierarchy', TType::LST, 60);
      {
        $output->writeListBegin(TType::STRING, count($this->hierarchy));
        {
          foreach ($this->hierarchy as $iter20)
          {
            $xfer += $output->writeString($iter20);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->selected !== null) {
      $xfer += $output->writeFieldBegin('selected', TType::BOOL, 70);
      $xfer += $output->writeBool($this->selected);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}