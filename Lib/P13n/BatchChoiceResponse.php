<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
use Thrift\Exception\TProtocolException;
class BatchChoiceResponse {
  static $_TSPEC;

  /**
   * @var \com\boxalino\p13n\api\thrift\Variant[]
   */
  public $variants = null;
  /**
   * @var (\com\boxalino\p13n\api\thrift\Variant[])[]
   */
  public $selectedVariants = null;

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
        2 => array(
          'var' => 'selectedVariants',
          'type' => TType::LST,
          'etype' => TType::LST,
          'elem' => array(
            'type' => TType::LST,
            'etype' => TType::STRUCT,
            'elem' => array(
              'type' => TType::STRUCT,
              'class' => '\com\boxalino\p13n\api\thrift\Variant',
              ),
            ),
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['variants'])) {
        $this->variants = $vals['variants'];
      }
      if (isset($vals['selectedVariants'])) {
        $this->selectedVariants = $vals['selectedVariants'];
      }
    }
  }

  public function getName() {
    return 'BatchChoiceResponse';
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
            $_size229 = 0;
            $_etype232 = 0;
            $xfer += $input->readListBegin($_etype232, $_size229);
            for ($_i233 = 0; $_i233 < $_size229; ++$_i233)
            {
              $elem234 = null;
              $elem234 = new \com\boxalino\p13n\api\thrift\Variant();
              $xfer += $elem234->read($input);
              $this->variants []= $elem234;
            }
            $xfer += $input->readListEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::LST) {
            $this->selectedVariants = array();
            $_size235 = 0;
            $_etype238 = 0;
            $xfer += $input->readListBegin($_etype238, $_size235);
            for ($_i239 = 0; $_i239 < $_size235; ++$_i239)
            {
              $elem240 = null;
              $elem240 = array();
              $_size241 = 0;
              $_etype244 = 0;
              $xfer += $input->readListBegin($_etype244, $_size241);
              for ($_i245 = 0; $_i245 < $_size241; ++$_i245)
              {
                $elem246 = null;
                $elem246 = new \com\boxalino\p13n\api\thrift\Variant();
                $xfer += $elem246->read($input);
                $elem240 []= $elem246;
              }
              $xfer += $input->readListEnd();
              $this->selectedVariants []= $elem240;
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
    $xfer += $output->writeStructBegin('BatchChoiceResponse');
    if ($this->variants !== null) {
      if (!is_array($this->variants)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('variants', TType::LST, 1);
      {
        $output->writeListBegin(TType::STRUCT, count($this->variants));
        {
          foreach ($this->variants as $iter247)
          {
            $xfer += $iter247->write($output);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->selectedVariants !== null) {
      if (!is_array($this->selectedVariants)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('selectedVariants', TType::LST, 2);
      {
        $output->writeListBegin(TType::LST, count($this->selectedVariants));
        {
          foreach ($this->selectedVariants as $iter248)
          {
            {
              $output->writeListBegin(TType::STRUCT, count($iter248));
              {
                foreach ($iter248 as $iter249)
                {
                  $xfer += $iter249->write($output);
                }
              }
              $output->writeListEnd();
            }
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