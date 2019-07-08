<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
use Thrift\Exception\TProtocolException;
class ChoiceUpdateRequest {
  static $_TSPEC;

  /**
   * user record identifying the client
   * 
   * @var \com\boxalino\p13n\api\thrift\UserRecord
   */
  public $userRecord = null;
  /**
   * Identifier of the choice to be changed. If it is not given, a new choice will be created
   * 
   * @var string
   */
  public $choiceId = null;
  /**
   * Map containing variant identifier and corresponding positive integer weight.
   * If for a choice there is no learned rule which can be applied, weights of
   * variants will be used for variants random distribution.
   * Higher weight makes corresponding variant more probable.
   * 
   * @var array
   */
  public $variantIds = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        11 => array(
          'var' => 'userRecord',
          'type' => TType::STRUCT,
          'class' => '\com\boxalino\p13n\api\thrift\UserRecord',
          ),
        21 => array(
          'var' => 'choiceId',
          'type' => TType::STRING,
          ),
        31 => array(
          'var' => 'variantIds',
          'type' => TType::MAP,
          'ktype' => TType::STRING,
          'vtype' => TType::I32,
          'key' => array(
            'type' => TType::STRING,
          ),
          'val' => array(
            'type' => TType::I32,
            ),
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['userRecord'])) {
        $this->userRecord = $vals['userRecord'];
      }
      if (isset($vals['choiceId'])) {
        $this->choiceId = $vals['choiceId'];
      }
      if (isset($vals['variantIds'])) {
        $this->variantIds = $vals['variantIds'];
      }
    }
  }

  public function getName() {
    return 'ChoiceUpdateRequest';
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
          if ($ftype == TType::STRUCT) {
            $this->userRecord = new \com\boxalino\p13n\api\thrift\UserRecord();
            $xfer += $this->userRecord->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 21:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->choiceId);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 31:
          if ($ftype == TType::MAP) {
            $this->variantIds = [];
            $_size317 = 0;
            $_ktype318 = 0;
            $_vtype319 = 0;
            $xfer += $input->readMapBegin($_ktype318, $_vtype319, $_size317);
            for ($_i321 = 0; $_i321 < $_size317; ++$_i321)
            {
              $key322 = '';
              $val323 = 0;
              $xfer += $input->readString($key322);
              $xfer += $input->readI32($val323);
              $this->variantIds[$key322] = $val323;
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
    $xfer += $output->writeStructBegin('ChoiceUpdateRequest');
    if ($this->userRecord !== null) {
      if (!is_object($this->userRecord)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('userRecord', TType::STRUCT, 11);
      $xfer += $this->userRecord->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->choiceId !== null) {
      $xfer += $output->writeFieldBegin('choiceId', TType::STRING, 21);
      $xfer += $output->writeString($this->choiceId);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->variantIds !== null) {
      if (!is_array($this->variantIds)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('variantIds', TType::MAP, 31);
      {
        $output->writeMapBegin(TType::STRING, TType::I32, count($this->variantIds));
        {
          foreach ($this->variantIds as $kiter324 => $viter325)
          {
            $xfer += $output->writeString($kiter324);
            $xfer += $output->writeI32($viter325);
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