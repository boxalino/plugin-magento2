<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
use Thrift\Exception\TProtocolException;
class P13nService_autocompleteAll_args {
  static $_TSPEC;

  /**
   * @var \com\boxalino\p13n\api\thrift\AutocompleteRequestBundle
   */
  public $bundle = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        -1 => array(
          'var' => 'bundle',
          'type' => TType::STRUCT,
          'class' => '\com\boxalino\p13n\api\thrift\AutocompleteRequestBundle',
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['bundle'])) {
        $this->bundle = $vals['bundle'];
      }
    }
  }

  public function getName() {
    return 'P13nService_autocompleteAll_args';
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
            $this->bundle = new \com\boxalino\p13n\api\thrift\AutocompleteRequestBundle();
            $xfer += $this->bundle->read($input);
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
    $xfer += $output->writeStructBegin('P13nService_autocompleteAll_args');
    if ($this->bundle !== null) {
      if (!is_object($this->bundle)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('bundle', TType::STRUCT, -1);
      $xfer += $this->bundle->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}