<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
class UserRecord {
  static $_TSPEC;

  /**
   * @var string
   */
  public $username = null;
  /**
   * @var string
   */
  public $apiKey = null;
  /**
   * @var string
   */
  public $apiSecret = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'username',
          'type' => TType::STRING,
          ),
        10 => array(
          'var' => 'apiKey',
          'type' => TType::STRING,
          ),
        20 => array(
          'var' => 'apiSecret',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['username'])) {
        $this->username = $vals['username'];
      }
      if (isset($vals['apiKey'])) {
        $this->apiKey = $vals['apiKey'];
      }
      if (isset($vals['apiSecret'])) {
        $this->apiSecret = $vals['apiSecret'];
      }
    }
  }

  public function getName() {
    return 'UserRecord';
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
            $xfer += $input->readString($this->username);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 10:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->apiKey);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 20:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->apiSecret);
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
    $xfer += $output->writeStructBegin('UserRecord');
    if ($this->username !== null) {
      $xfer += $output->writeFieldBegin('username', TType::STRING, 1);
      $xfer += $output->writeString($this->username);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->apiKey !== null) {
      $xfer += $output->writeFieldBegin('apiKey', TType::STRING, 10);
      $xfer += $output->writeString($this->apiKey);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->apiSecret !== null) {
      $xfer += $output->writeFieldBegin('apiSecret', TType::STRING, 20);
      $xfer += $output->writeString($this->apiSecret);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}