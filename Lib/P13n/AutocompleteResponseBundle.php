<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
use Thrift\Exception\TProtocolException;
class AutocompleteResponseBundle {
  static $_TSPEC;

  /**
   * @var \com\boxalino\p13n\api\thrift\AutocompleteResponse[]
   */
  public $responses = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        11 => array(
          'var' => 'responses',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => '\com\boxalino\p13n\api\thrift\AutocompleteResponse',
            ),
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['responses'])) {
        $this->responses = $vals['responses'];
      }
    }
  }

  public function getName() {
    return 'AutocompleteResponseBundle';
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
            $this->responses = array();
            $_size310 = 0;
            $_etype313 = 0;
            $xfer += $input->readListBegin($_etype313, $_size310);
            for ($_i314 = 0; $_i314 < $_size310; ++$_i314)
            {
              $elem315 = null;
              $elem315 = new \com\boxalino\p13n\api\thrift\AutocompleteResponse();
              $xfer += $elem315->read($input);
              $this->responses []= $elem315;
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
    $xfer += $output->writeStructBegin('AutocompleteResponseBundle');
    if ($this->responses !== null) {
      if (!is_array($this->responses)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('responses', TType::LST, 11);
      {
        $output->writeListBegin(TType::STRUCT, count($this->responses));
        {
          foreach ($this->responses as $iter316)
          {
            $xfer += $iter316->write($output);
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