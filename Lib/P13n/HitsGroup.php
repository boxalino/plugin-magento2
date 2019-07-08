<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
use Thrift\Exception\TProtocolException;
class HitsGroup {
  static $_TSPEC;

  /**
   * @var string
   */
  public $groupValue = null;
  /**
   * @var int
   */
  public $totalHitCount = null;
  /**
   * @var \com\boxalino\p13n\api\thrift\Hit[]
   */
  public $hits = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        10 => array(
          'var' => 'groupValue',
          'type' => TType::STRING,
          ),
        20 => array(
          'var' => 'totalHitCount',
          'type' => TType::I64,
          ),
        30 => array(
          'var' => 'hits',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => '\com\boxalino\p13n\api\thrift\Hit',
            ),
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['groupValue'])) {
        $this->groupValue = $vals['groupValue'];
      }
      if (isset($vals['totalHitCount'])) {
        $this->totalHitCount = $vals['totalHitCount'];
      }
      if (isset($vals['hits'])) {
        $this->hits = $vals['hits'];
      }
    }
  }

  public function getName() {
    return 'HitsGroup';
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
        case 10:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->groupValue);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 20:
          if ($ftype == TType::I64) {
            $xfer += $input->readI64($this->totalHitCount);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 30:
          if ($ftype == TType::LST) {
            $this->hits = [];
            $_size143 = 0;
            $_etype146 = 0;
            $xfer += $input->readListBegin($_etype146, $_size143);
            for ($_i147 = 0; $_i147 < $_size143; ++$_i147)
            {
              $elem148 = null;
              $elem148 = new \com\boxalino\p13n\api\thrift\Hit();
              $xfer += $elem148->read($input);
              $this->hits []= $elem148;
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
    $xfer += $output->writeStructBegin('HitsGroup');
    if ($this->groupValue !== null) {
      $xfer += $output->writeFieldBegin('groupValue', TType::STRING, 10);
      $xfer += $output->writeString($this->groupValue);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->totalHitCount !== null) {
      $xfer += $output->writeFieldBegin('totalHitCount', TType::I64, 20);
      $xfer += $output->writeI64($this->totalHitCount);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->hits !== null) {
      if (!is_array($this->hits)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('hits', TType::LST, 30);
      {
        $output->writeListBegin(TType::STRUCT, count($this->hits));
        {
          foreach ($this->hits as $iter149)
          {
            $xfer += $iter149->write($output);
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