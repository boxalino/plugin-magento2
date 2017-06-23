<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
use Thrift\Exception\TProtocolException;
class RequestContext {
  static $_TSPEC;

  /**
   * @var array
   */
  public $parameters = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'parameters',
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
        );
    }
    if (is_array($vals)) {
      if (isset($vals['parameters'])) {
        $this->parameters = $vals['parameters'];
      }
    }
  }

  public function getName() {
    return 'RequestContext';
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
            $this->parameters = array();
            $_size88 = 0;
            $_ktype89 = 0;
            $_vtype90 = 0;
            $xfer += $input->readMapBegin($_ktype89, $_vtype90, $_size88);
            for ($_i92 = 0; $_i92 < $_size88; ++$_i92)
            {
              $key93 = '';
              $val94 = array();
              $xfer += $input->readString($key93);
              $val94 = array();
              $_size95 = 0;
              $_etype98 = 0;
              $xfer += $input->readListBegin($_etype98, $_size95);
              for ($_i99 = 0; $_i99 < $_size95; ++$_i99)
              {
                $elem100 = null;
                $xfer += $input->readString($elem100);
                $val94 []= $elem100;
              }
              $xfer += $input->readListEnd();
              $this->parameters[$key93] = $val94;
            }
            $xfer += $input->readMapEnd();
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
    $xfer += $output->writeStructBegin('RequestContext');
    if ($this->parameters !== null) {
      if (!is_array($this->parameters)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('parameters', TType::MAP, 1);
      {
        $output->writeMapBegin(TType::STRING, TType::LST, count($this->parameters));
        {
          foreach ($this->parameters as $kiter101 => $viter102)
          {
            $xfer += $output->writeString($kiter101);
            {
              $output->writeListBegin(TType::STRING, count($viter102));
              {
                foreach ($viter102 as $iter103)
                {
                  $xfer += $output->writeString($iter103);
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
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}