<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
class ProfilePropertyValue {
  static $_TSPEC;

  /**
   * @var string
   */
  public $profileId = null;
  /**
   * @var string
   */
  public $propertyName = null;
  /**
   * @var string
   */
  public $propertyValue = null;
  /**
   * @var int
   */
  public $confidence = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'profileId',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'propertyName',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'propertyValue',
          'type' => TType::STRING,
          ),
        4 => array(
          'var' => 'confidence',
          'type' => TType::I32,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['profileId'])) {
        $this->profileId = $vals['profileId'];
      }
      if (isset($vals['propertyName'])) {
        $this->propertyName = $vals['propertyName'];
      }
      if (isset($vals['propertyValue'])) {
        $this->propertyValue = $vals['propertyValue'];
      }
      if (isset($vals['confidence'])) {
        $this->confidence = $vals['confidence'];
      }
    }
  }

  public function getName() {
    return 'ProfilePropertyValue';
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
            $xfer += $input->readString($this->profileId);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->propertyName);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->propertyValue);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->confidence);
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
    $xfer += $output->writeStructBegin('ProfilePropertyValue');
    if ($this->profileId !== null) {
      $xfer += $output->writeFieldBegin('profileId', TType::STRING, 1);
      $xfer += $output->writeString($this->profileId);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->propertyName !== null) {
      $xfer += $output->writeFieldBegin('propertyName', TType::STRING, 2);
      $xfer += $output->writeString($this->propertyName);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->propertyValue !== null) {
      $xfer += $output->writeFieldBegin('propertyValue', TType::STRING, 3);
      $xfer += $output->writeString($this->propertyValue);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->confidence !== null) {
      $xfer += $output->writeFieldBegin('confidence', TType::I32, 4);
      $xfer += $output->writeI32($this->confidence);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}