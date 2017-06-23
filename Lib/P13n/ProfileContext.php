<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
use Thrift\Exception\TProtocolException;
class ProfileContext {
  static $_TSPEC;

  /**
   * @var string
   */
  public $profileId = null;
  /**
   * @var \com\boxalino\p13n\api\thrift\RequestContext
   */
  public $requestContext = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'profileId',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'requestContext',
          'type' => TType::STRUCT,
          'class' => '\com\boxalino\p13n\api\thrift\RequestContext',
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['profileId'])) {
        $this->profileId = $vals['profileId'];
      }
      if (isset($vals['requestContext'])) {
        $this->requestContext = $vals['requestContext'];
      }
    }
  }

  public function getName() {
    return 'ProfileContext';
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
          if ($ftype == TType::STRUCT) {
            $this->requestContext = new \com\boxalino\p13n\api\thrift\RequestContext();
            $xfer += $this->requestContext->read($input);
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
    $xfer += $output->writeStructBegin('ProfileContext');
    if ($this->profileId !== null) {
      $xfer += $output->writeFieldBegin('profileId', TType::STRING, 1);
      $xfer += $output->writeString($this->profileId);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->requestContext !== null) {
      if (!is_object($this->requestContext)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('requestContext', TType::STRUCT, 2);
      $xfer += $this->requestContext->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}