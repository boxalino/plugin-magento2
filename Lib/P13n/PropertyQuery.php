<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
class PropertyQuery {
  static $_TSPEC;

  /**
   * @var string
   */
  public $name = null;
  /**
   * @var int
   */
  public $hitCount = null;
  /**
   * @var bool
   */
  public $evaluateTotal = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        11 => array(
          'var' => 'name',
          'type' => TType::STRING,
          ),
        21 => array(
          'var' => 'hitCount',
          'type' => TType::I32,
          ),
        31 => array(
          'var' => 'evaluateTotal',
          'type' => TType::BOOL,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['name'])) {
        $this->name = $vals['name'];
      }
      if (isset($vals['hitCount'])) {
        $this->hitCount = $vals['hitCount'];
      }
      if (isset($vals['evaluateTotal'])) {
        $this->evaluateTotal = $vals['evaluateTotal'];
      }
    }
  }

  public function getName() {
    return 'PropertyQuery';
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
            $xfer += $input->readString($this->name);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 21:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->hitCount);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 31:
          if ($ftype == TType::BOOL) {
            $xfer += $input->readBool($this->evaluateTotal);
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
    $xfer += $output->writeStructBegin('PropertyQuery');
    if ($this->name !== null) {
      $xfer += $output->writeFieldBegin('name', TType::STRING, 11);
      $xfer += $output->writeString($this->name);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->hitCount !== null) {
      $xfer += $output->writeFieldBegin('hitCount', TType::I32, 21);
      $xfer += $output->writeI32($this->hitCount);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->evaluateTotal !== null) {
      $xfer += $output->writeFieldBegin('evaluateTotal', TType::BOOL, 31);
      $xfer += $output->writeBool($this->evaluateTotal);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}