<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
class PropertyHit {
  static $_TSPEC;

  /**
   * @var string
   */
  public $value = null;
  /**
   * @var string
   */
  public $label = null;
  /**
   * @var int
   */
  public $totalHitCount = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        11 => array(
          'var' => 'value',
          'type' => TType::STRING,
          ),
        21 => array(
          'var' => 'label',
          'type' => TType::STRING,
          ),
        31 => array(
          'var' => 'totalHitCount',
          'type' => TType::I64,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['value'])) {
        $this->value = $vals['value'];
      }
      if (isset($vals['label'])) {
        $this->label = $vals['label'];
      }
      if (isset($vals['totalHitCount'])) {
        $this->totalHitCount = $vals['totalHitCount'];
      }
    }
  }

  public function getName() {
    return 'PropertyHit';
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
            $xfer += $input->readString($this->value);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 21:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->label);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 31:
          if ($ftype == TType::I64) {
            $xfer += $input->readI64($this->totalHitCount);
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
    $xfer += $output->writeStructBegin('PropertyHit');
    if ($this->value !== null) {
      $xfer += $output->writeFieldBegin('value', TType::STRING, 11);
      $xfer += $output->writeString($this->value);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->label !== null) {
      $xfer += $output->writeFieldBegin('label', TType::STRING, 21);
      $xfer += $output->writeString($this->label);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->totalHitCount !== null) {
      $xfer += $output->writeFieldBegin('totalHitCount', TType::I64, 31);
      $xfer += $output->writeI64($this->totalHitCount);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}