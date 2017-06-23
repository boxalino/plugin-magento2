<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
class AutocompleteQuery {
  static $_TSPEC;

  /**
   * @var string
   */
  public $indexId = null;
  /**
   * @var string
   */
  public $language = null;
  /**
   * @var string
   */
  public $queryText = null;
  /**
   * @var int
   */
  public $suggestionsHitCount = null;
  /**
   * @var bool
   */
  public $highlight = null;
  /**
   * @var string
   */
  public $highlightPre = "<em>";
  /**
   * @var string
   */
  public $highlightPost = "</em>";

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        11 => array(
          'var' => 'indexId',
          'type' => TType::STRING,
          ),
        21 => array(
          'var' => 'language',
          'type' => TType::STRING,
          ),
        31 => array(
          'var' => 'queryText',
          'type' => TType::STRING,
          ),
        41 => array(
          'var' => 'suggestionsHitCount',
          'type' => TType::I32,
          ),
        51 => array(
          'var' => 'highlight',
          'type' => TType::BOOL,
          ),
        61 => array(
          'var' => 'highlightPre',
          'type' => TType::STRING,
          ),
        71 => array(
          'var' => 'highlightPost',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['indexId'])) {
        $this->indexId = $vals['indexId'];
      }
      if (isset($vals['language'])) {
        $this->language = $vals['language'];
      }
      if (isset($vals['queryText'])) {
        $this->queryText = $vals['queryText'];
      }
      if (isset($vals['suggestionsHitCount'])) {
        $this->suggestionsHitCount = $vals['suggestionsHitCount'];
      }
      if (isset($vals['highlight'])) {
        $this->highlight = $vals['highlight'];
      }
      if (isset($vals['highlightPre'])) {
        $this->highlightPre = $vals['highlightPre'];
      }
      if (isset($vals['highlightPost'])) {
        $this->highlightPost = $vals['highlightPost'];
      }
    }
  }

  public function getName() {
    return 'AutocompleteQuery';
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
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->indexId);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 21:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->language);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 31:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->queryText);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 41:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->suggestionsHitCount);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 51:
          if ($ftype == TType::BOOL) {
            $xfer += $input->readBool($this->highlight);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 61:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->highlightPre);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 71:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->highlightPost);
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
    $xfer += $output->writeStructBegin('AutocompleteQuery');
    if ($this->indexId !== null) {
      $xfer += $output->writeFieldBegin('indexId', TType::STRING, 11);
      $xfer += $output->writeString($this->indexId);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->language !== null) {
      $xfer += $output->writeFieldBegin('language', TType::STRING, 21);
      $xfer += $output->writeString($this->language);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->queryText !== null) {
      $xfer += $output->writeFieldBegin('queryText', TType::STRING, 31);
      $xfer += $output->writeString($this->queryText);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->suggestionsHitCount !== null) {
      $xfer += $output->writeFieldBegin('suggestionsHitCount', TType::I32, 41);
      $xfer += $output->writeI32($this->suggestionsHitCount);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->highlight !== null) {
      $xfer += $output->writeFieldBegin('highlight', TType::BOOL, 51);
      $xfer += $output->writeBool($this->highlight);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->highlightPre !== null) {
      $xfer += $output->writeFieldBegin('highlightPre', TType::STRING, 61);
      $xfer += $output->writeString($this->highlightPre);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->highlightPost !== null) {
      $xfer += $output->writeFieldBegin('highlightPost', TType::STRING, 71);
      $xfer += $output->writeString($this->highlightPost);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}