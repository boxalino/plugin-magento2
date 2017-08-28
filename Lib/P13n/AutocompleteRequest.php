<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
use Thrift\Exception\TProtocolException;
class AutocompleteRequest {
  static $_TSPEC;

  /**
   * @var \com\boxalino\p13n\api\thrift\UserRecord
   */
  public $userRecord = null;
  /**
   * @var string
   */
  public $scope = "system_rec";
  /**
   * @var string
   */
  public $choiceId = null;
  /**
   * @var string
   */
  public $profileId = null;
  /**
   * @var \com\boxalino\p13n\api\thrift\RequestContext
   */
  public $requestContext = null;
  /**
   * @var string[]
   */
  public $excludeVariantIds = null;
  /**
   * @var \com\boxalino\p13n\api\thrift\AutocompleteQuery
   */
  public $autocompleteQuery = null;
  /**
   * @var string
   */
  public $searchChoiceId = null;
  /**
   * @var \com\boxalino\p13n\api\thrift\SimpleSearchQuery
   */
  public $searchQuery = null;
  /**
   * @var string[]
   */
  public $includeVariantIds = null;
  /**
   * @var \com\boxalino\p13n\api\thrift\PropertyQuery[]
   */
  public $propertyQueries = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        11 => array(
          'var' => 'userRecord',
          'type' => TType::STRUCT,
          'class' => '\com\boxalino\p13n\api\thrift\UserRecord',
          ),
        21 => array(
          'var' => 'scope',
          'type' => TType::STRING,
          ),
        31 => array(
          'var' => 'choiceId',
          'type' => TType::STRING,
          ),
        41 => array(
          'var' => 'profileId',
          'type' => TType::STRING,
          ),
        51 => array(
          'var' => 'requestContext',
          'type' => TType::STRUCT,
          'class' => '\com\boxalino\p13n\api\thrift\RequestContext',
          ),
        61 => array(
          'var' => 'excludeVariantIds',
          'type' => TType::SET,
          'etype' => TType::STRING,
          'elem' => array(
            'type' => TType::STRING,
            ),
          ),
        71 => array(
          'var' => 'autocompleteQuery',
          'type' => TType::STRUCT,
          'class' => '\com\boxalino\p13n\api\thrift\AutocompleteQuery',
          ),
        81 => array(
          'var' => 'searchChoiceId',
          'type' => TType::STRING,
          ),
        91 => array(
          'var' => 'searchQuery',
          'type' => TType::STRUCT,
          'class' => '\com\boxalino\p13n\api\thrift\SimpleSearchQuery',
          ),
        101 => array(
          'var' => 'includeVariantIds',
          'type' => TType::SET,
          'etype' => TType::STRING,
          'elem' => array(
            'type' => TType::STRING,
            ),
          ),
        110 => array(
          'var' => 'propertyQueries',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => '\com\boxalino\p13n\api\thrift\PropertyQuery',
            ),
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['userRecord'])) {
        $this->userRecord = $vals['userRecord'];
      }
      if (isset($vals['scope'])) {
        $this->scope = $vals['scope'];
      }
      if (isset($vals['choiceId'])) {
        $this->choiceId = $vals['choiceId'];
      }
      if (isset($vals['profileId'])) {
        $this->profileId = $vals['profileId'];
      }
      if (isset($vals['requestContext'])) {
        $this->requestContext = $vals['requestContext'];
      }
      if (isset($vals['excludeVariantIds'])) {
        $this->excludeVariantIds = $vals['excludeVariantIds'];
      }
      if (isset($vals['autocompleteQuery'])) {
        $this->autocompleteQuery = $vals['autocompleteQuery'];
      }
      if (isset($vals['searchChoiceId'])) {
        $this->searchChoiceId = $vals['searchChoiceId'];
      }
      if (isset($vals['searchQuery'])) {
        $this->searchQuery = $vals['searchQuery'];
      }
      if (isset($vals['includeVariantIds'])) {
        $this->includeVariantIds = $vals['includeVariantIds'];
      }
      if (isset($vals['propertyQueries'])) {
        $this->propertyQueries = $vals['propertyQueries'];
      }
    }
  }

  public function getName() {
    return 'AutocompleteRequest';
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
            $xfer += $input->readString($this->scope);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 31:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->choiceId);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 41:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->profileId);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 51:
          if ($ftype == TType::STRUCT) {
            $this->requestContext = new \com\boxalino\p13n\api\thrift\RequestContext();
            $xfer += $this->requestContext->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 61:
          if ($ftype == TType::SET) {
            $this->excludeVariantIds = array();
            $_size250 = 0;
            $_etype253 = 0;
            $xfer += $input->readSetBegin($_etype253, $_size250);
            for ($_i254 = 0; $_i254 < $_size250; ++$_i254)
            {
              $elem255 = null;
              $xfer += $input->readString($elem255);
              if (is_scalar($elem255)) {
                $this->excludeVariantIds[$elem255] = true;
              } else {
                $this->excludeVariantIds []= $elem255;
              }
            }
            $xfer += $input->readSetEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 71:
          if ($ftype == TType::STRUCT) {
            $this->autocompleteQuery = new \com\boxalino\p13n\api\thrift\AutocompleteQuery();
            $xfer += $this->autocompleteQuery->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 81:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->searchChoiceId);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 91:
          if ($ftype == TType::STRUCT) {
            $this->searchQuery = new \com\boxalino\p13n\api\thrift\SimpleSearchQuery();
            $xfer += $this->searchQuery->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 101:
          if ($ftype == TType::SET) {
            $this->includeVariantIds = array();
            $_size256 = 0;
            $_etype259 = 0;
            $xfer += $input->readSetBegin($_etype259, $_size256);
            for ($_i260 = 0; $_i260 < $_size256; ++$_i260)
            {
              $elem261 = null;
              $xfer += $input->readString($elem261);
              if (is_scalar($elem261)) {
                $this->includeVariantIds[$elem261] = true;
              } else {
                $this->includeVariantIds []= $elem261;
              }
            }
            $xfer += $input->readSetEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 110:
          if ($ftype == TType::LST) {
            $this->propertyQueries = array();
            $_size262 = 0;
            $_etype265 = 0;
            $xfer += $input->readListBegin($_etype265, $_size262);
            for ($_i266 = 0; $_i266 < $_size262; ++$_i266)
            {
              $elem267 = null;
              $elem267 = new \com\boxalino\p13n\api\thrift\PropertyQuery();
              $xfer += $elem267->read($input);
              $this->propertyQueries []= $elem267;
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
    $xfer += $output->writeStructBegin('AutocompleteRequest');
    if ($this->userRecord !== null) {
      if (!is_object($this->userRecord)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('userRecord', TType::STRUCT, 11);
      $xfer += $this->userRecord->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->scope !== null) {
      $xfer += $output->writeFieldBegin('scope', TType::STRING, 21);
      $xfer += $output->writeString($this->scope);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->choiceId !== null) {
      $xfer += $output->writeFieldBegin('choiceId', TType::STRING, 31);
      $xfer += $output->writeString($this->choiceId);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->profileId !== null) {
      $xfer += $output->writeFieldBegin('profileId', TType::STRING, 41);
      $xfer += $output->writeString($this->profileId);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->requestContext !== null) {
      if (!is_object($this->requestContext)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('requestContext', TType::STRUCT, 51);
      $xfer += $this->requestContext->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->excludeVariantIds !== null) {
      if (!is_array($this->excludeVariantIds)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('excludeVariantIds', TType::SET, 61);
      {
        $output->writeSetBegin(TType::STRING, count($this->excludeVariantIds));
        {
          foreach ($this->excludeVariantIds as $iter268 => $iter269)
          {
            if (is_scalar($iter269)) {
            $xfer += $output->writeString($iter268);
            } else {
            $xfer += $output->writeString($iter269);
            }
          }
        }
        $output->writeSetEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->autocompleteQuery !== null) {
      if (!is_object($this->autocompleteQuery)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('autocompleteQuery', TType::STRUCT, 71);
      $xfer += $this->autocompleteQuery->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->searchChoiceId !== null) {
      $xfer += $output->writeFieldBegin('searchChoiceId', TType::STRING, 81);
      $xfer += $output->writeString($this->searchChoiceId);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->searchQuery !== null) {
      if (!is_object($this->searchQuery)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('searchQuery', TType::STRUCT, 91);
      $xfer += $this->searchQuery->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->includeVariantIds !== null) {
      if (!is_array($this->includeVariantIds)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('includeVariantIds', TType::SET, 101);
      {
        $output->writeSetBegin(TType::STRING, count($this->includeVariantIds));
        {
          foreach ($this->includeVariantIds as $iter270 => $iter271)
          {
            if (is_scalar($iter271)) {
            $xfer += $output->writeString($iter270);
            } else {
            $xfer += $output->writeString($iter271);
            }
          }
        }
        $output->writeSetEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->propertyQueries !== null) {
      if (!is_array($this->propertyQueries)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('propertyQueries', TType::LST, 110);
      {
        $output->writeListBegin(TType::STRUCT, count($this->propertyQueries));
        {
          foreach ($this->propertyQueries as $iter272)
          {
            $xfer += $iter272->write($output);
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