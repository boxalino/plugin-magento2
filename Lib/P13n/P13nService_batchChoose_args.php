<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
use Thrift\Exception\TProtocolException;
class P13nService_batchChoose_args {
  static $_TSPEC;

  /**
   * @var \com\boxalino\p13n\api\thrift\BatchChoiceRequest
   */
  public $batchChoiceRequest = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        -1 => array(
          'var' => 'batchChoiceRequest',
          'type' => TType::STRUCT,
          'class' => '\com\boxalino\p13n\api\thrift\BatchChoiceRequest',
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['batchChoiceRequest'])) {
        $this->batchChoiceRequest = $vals['batchChoiceRequest'];
      }
    }
  }

  public function getName() {
    return 'P13nService_batchChoose_args';
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
        case -1:
          if ($ftype == TType::STRUCT) {
            $this->batchChoiceRequest = new \com\boxalino\p13n\api\thrift\BatchChoiceRequest();
            $xfer += $this->batchChoiceRequest->read($input);
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
    $xfer += $output->writeStructBegin('P13nService_batchChoose_args');
    if ($this->batchChoiceRequest !== null) {
      if (!is_object($this->batchChoiceRequest)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('batchChoiceRequest', TType::STRUCT, -1);
      $xfer += $this->batchChoiceRequest->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}