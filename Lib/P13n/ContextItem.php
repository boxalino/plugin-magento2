<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
class ContextItem {
  static $_TSPEC;

  /**
   * @var string
   */
  public $indexId = null;
  /**
   * @var string
   */
  public $fieldName = null;
  /**
   * @var string
   */
  public $contextItemId = null;
  /**
   * @var string
   */
  public $role = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'indexId',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'fieldName',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'contextItemId',
          'type' => TType::STRING,
          ),
        4 => array(
          'var' => 'role',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['indexId'])) {
        $this->indexId = $vals['indexId'];
      }
      if (isset($vals['fieldName'])) {
        $this->fieldName = $vals['fieldName'];
      }
      if (isset($vals['contextItemId'])) {
        $this->contextItemId = $vals['contextItemId'];
      }
      if (isset($vals['role'])) {
        $this->role = $vals['role'];
      }
    }
  }

  public function getName() {
    return 'ContextItem';
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
            $xfer += $input->readString($this->indexId);
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
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->contextItemId);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->role);
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
    $xfer += $output->writeStructBegin('ContextItem');
    if ($this->indexId !== null) {
      $xfer += $output->writeFieldBegin('indexId', TType::STRING, 1);
      $xfer += $output->writeString($this->indexId);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->fieldName !== null) {
      $xfer += $output->writeFieldBegin('fieldName', TType::STRING, 2);
      $xfer += $output->writeString($this->fieldName);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->contextItemId !== null) {
      $xfer += $output->writeFieldBegin('contextItemId', TType::STRING, 3);
      $xfer += $output->writeString($this->contextItemId);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->role !== null) {
      $xfer += $output->writeFieldBegin('role', TType::STRING, 4);
      $xfer += $output->writeString($this->role);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}