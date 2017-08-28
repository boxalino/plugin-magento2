<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
use Thrift\Exception\TProtocolException;
class AutocompleteRequestBundle {
  static $_TSPEC;

  /**
   * @var \com\boxalino\p13n\api\thrift\AutocompleteRequest[]
   */
  public $requests = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        11 => array(
          'var' => 'requests',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => '\com\boxalino\p13n\api\thrift\AutocompleteRequest',
            ),
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['requests'])) {
        $this->requests = $vals['requests'];
      }
    }
  }

  public function getName() {
    return 'AutocompleteRequestBundle';
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
        case 11:
          if ($ftype == TType::LST) {
            $this->requests = array();
            $_size303 = 0;
            $_etype306 = 0;
            $xfer += $input->readListBegin($_etype306, $_size303);
            for ($_i307 = 0; $_i307 < $_size303; ++$_i307)
            {
              $elem308 = null;
              $elem308 = new \com\boxalino\p13n\api\thrift\AutocompleteRequest();
              $xfer += $elem308->read($input);
              $this->requests []= $elem308;
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
    $xfer += $output->writeStructBegin('AutocompleteRequestBundle');
    if ($this->requests !== null) {
      if (!is_array($this->requests)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('requests', TType::LST, 11);
      {
        $output->writeListBegin(TType::STRUCT, count($this->requests));
        {
          foreach ($this->requests as $iter309)
          {
            $xfer += $iter309->write($output);
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