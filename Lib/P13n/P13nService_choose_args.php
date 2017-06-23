<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
use Thrift\Exception\TProtocolException;
class P13nService_choose_args {
  static $_TSPEC;

  /**
   * @var \com\boxalino\p13n\api\thrift\ChoiceRequest
   */
  public $choiceRequest = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        -1 => array(
          'var' => 'choiceRequest',
          'type' => TType::STRUCT,
          'class' => '\com\boxalino\p13n\api\thrift\ChoiceRequest',
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['choiceRequest'])) {
        $this->choiceRequest = $vals['choiceRequest'];
      }
    }
  }

  public function getName() {
    return 'P13nService_choose_args';
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
            $this->choiceRequest = new \com\boxalino\p13n\api\thrift\ChoiceRequest();
            $xfer += $this->choiceRequest->read($input);
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
    $xfer += $output->writeStructBegin('P13nService_choose_args');
    if ($this->choiceRequest !== null) {
      if (!is_object($this->choiceRequest)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('choiceRequest', TType::STRUCT, -1);
      $xfer += $this->choiceRequest->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}