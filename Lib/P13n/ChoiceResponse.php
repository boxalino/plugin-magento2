<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
use Thrift\Exception\TProtocolException;
class ChoiceResponse {
  static $_TSPEC;

  /**
   * @var \com\boxalino\p13n\api\thrift\Variant[]
   */
  public $variants = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'variants',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => '\com\boxalino\p13n\api\thrift\Variant',
            ),
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['variants'])) {
        $this->variants = $vals['variants'];
      }
    }
  }

  public function getName() {
    return 'ChoiceResponse';
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
          if ($ftype == TType::LST) {
            $this->variants = array();
            $_size201 = 0;
            $_etype204 = 0;
            $xfer += $input->readListBegin($_etype204, $_size201);
            for ($_i205 = 0; $_i205 < $_size201; ++$_i205)
            {
              $elem206 = null;
              $elem206 = new \com\boxalino\p13n\api\thrift\Variant();
              $xfer += $elem206->read($input);
              $this->variants []= $elem206;
            }
            $xfer += $input->readListEnd();
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
    $xfer += $output->writeStructBegin('ChoiceResponse');
    if ($this->variants !== null) {
      if (!is_array($this->variants)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('variants', TType::LST, 1);
      {
        $output->writeListBegin(TType::STRUCT, count($this->variants));
        {
          foreach ($this->variants as $iter207)
          {
            $xfer += $iter207->write($output);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}