<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
use Thrift\Exception\TProtocolException;
class Hit {
  static $_TSPEC;

  /**
   * @var array
   */
  public $values = null;
  /**
   * @var double
   */
  public $score = null;
  /**
   * @var string
   */
  public $scenarioId = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'values',
          'type' => TType::MAP,
          'ktype' => TType::STRING,
          'vtype' => TType::LST,
          'key' => array(
            'type' => TType::STRING,
          ),
          'val' => array(
            'type' => TType::LST,
            'etype' => TType::STRING,
            'elem' => array(
              'type' => TType::STRING,
              ),
            ),
          ),
        2 => array(
          'var' => 'score',
          'type' => TType::DOUBLE,
          ),
        30 => array(
          'var' => 'scenarioId',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['values'])) {
        $this->values = $vals['values'];
      }
      if (isset($vals['score'])) {
        $this->score = $vals['score'];
      }
      if (isset($vals['scenarioId'])) {
        $this->scenarioId = $vals['scenarioId'];
      }
    }
  }

  public function getName() {
    return 'Hit';
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
          if ($ftype == TType::MAP) {
            $this->values = array();
            $_size127 = 0;
            $_ktype128 = 0;
            $_vtype129 = 0;
            $xfer += $input->readMapBegin($_ktype128, $_vtype129, $_size127);
            for ($_i131 = 0; $_i131 < $_size127; ++$_i131)
            {
              $key132 = '';
              $val133 = array();
              $xfer += $input->readString($key132);
              $val133 = array();
              $_size134 = 0;
              $_etype137 = 0;
              $xfer += $input->readListBegin($_etype137, $_size134);
              for ($_i138 = 0; $_i138 < $_size134; ++$_i138)
              {
                $elem139 = null;
                $xfer += $input->readString($elem139);
                $val133 []= $elem139;
              }
              $xfer += $input->readListEnd();
              $this->values[$key132] = $val133;
            }
            $xfer += $input->readMapEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::DOUBLE) {
            $xfer += $input->readDouble($this->score);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 30:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->scenarioId);
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
    $xfer += $output->writeStructBegin('Hit');
    if ($this->values !== null) {
      if (!is_array($this->values)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('values', TType::MAP, 1);
      {
        $output->writeMapBegin(TType::STRING, TType::LST, count($this->values));
        {
          foreach ($this->values as $kiter140 => $viter141)
          {
            $xfer += $output->writeString($kiter140);
            {
              $output->writeListBegin(TType::STRING, count($viter141));
              {
                foreach ($viter141 as $iter142)
                {
                  $xfer += $output->writeString($iter142);
                }
              }
              $output->writeListEnd();
            }
          }
        }
        $output->writeMapEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->score !== null) {
      $xfer += $output->writeFieldBegin('score', TType::DOUBLE, 2);
      $xfer += $output->writeDouble($this->score);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->scenarioId !== null) {
      $xfer += $output->writeFieldBegin('scenarioId', TType::STRING, 30);
      $xfer += $output->writeString($this->scenarioId);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}