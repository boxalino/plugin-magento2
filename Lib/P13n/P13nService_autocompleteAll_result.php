<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
use Thrift\Exception\TProtocolException;
class P13nService_autocompleteAll_result {
  static $_TSPEC;

  /**
   * @var \com\boxalino\p13n\api\thrift\AutocompleteResponseBundle
   */
  public $success = null;
  /**
   * @var \com\boxalino\p13n\api\thrift\P13nServiceException
   */
  public $p13nServiceException = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        0 => array(
          'var' => 'success',
          'type' => TType::STRUCT,
          'class' => '\com\boxalino\p13n\api\thrift\AutocompleteResponseBundle',
          ),
        1 => array(
          'var' => 'p13nServiceException',
          'type' => TType::STRUCT,
          'class' => '\com\boxalino\p13n\api\thrift\P13nServiceException',
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['success'])) {
        $this->success = $vals['success'];
      }
      if (isset($vals['p13nServiceException'])) {
        $this->p13nServiceException = $vals['p13nServiceException'];
      }
    }
  }

  public function getName() {
    return 'P13nService_autocompleteAll_result';
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
        case 0:
          if ($ftype == TType::STRUCT) {
            $this->success = new \com\boxalino\p13n\api\thrift\AutocompleteResponseBundle();
            $xfer += $this->success->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 1:
          if ($ftype == TType::STRUCT) {
            $this->p13nServiceException = new \com\boxalino\p13n\api\thrift\P13nServiceException();
            $xfer += $this->p13nServiceException->read($input);
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
    $xfer += $output->writeStructBegin('P13nService_autocompleteAll_result');
    if ($this->success !== null) {
      if (!is_object($this->success)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('success', TType::STRUCT, 0);
      $xfer += $this->success->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->p13nServiceException !== null) {
      $xfer += $output->writeFieldBegin('p13nServiceException', TType::STRUCT, 1);
      $xfer += $this->p13nServiceException->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}