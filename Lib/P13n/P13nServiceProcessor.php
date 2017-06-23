<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Type\TType;
use Thrift\Exception\TApplicationException;
use Thrift\Type\TMessageType;
use Thrift\Protocol\TBinaryProtocolAccelerated;
class P13nServiceProcessor {
  protected $handler_ = null;
  public function __construct($handler) {
    $this->handler_ = $handler;
  }

  public function process($input, $output) {
    $rseqid = 0;
    $fname = null;
    $mtype = 0;

    $input->readMessageBegin($fname, $mtype, $rseqid);
    $methodname = 'process_'.$fname;
    if (!method_exists($this, $methodname)) {
      $input->skip(TType::STRUCT);
      $input->readMessageEnd();
      $x = new TApplicationException('Function '.$fname.' not implemented.', TApplicationException::UNKNOWN_METHOD);
      $output->writeMessageBegin($fname, TMessageType::EXCEPTION, $rseqid);
      $x->write($output);
      $output->writeMessageEnd();
      $output->getTransport()->flush();
      return;
    }
    $this->$methodname($rseqid, $input, $output);
    return true;
  }

  protected function process_choose($seqid, $input, $output) {
    $args = new \com\boxalino\p13n\api\thrift\P13nService_choose_args();
    $args->read($input);
    $input->readMessageEnd();
    $result = new \com\boxalino\p13n\api\thrift\P13nService_choose_result();
    try {
      $result->success = $this->handler_->choose($args->choiceRequest);
    } catch (\com\boxalino\p13n\api\thrift\P13nServiceException $p13nServiceException) {
      $result->p13nServiceException = $p13nServiceException;
    }
    $bin_accel = ($output instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($output, 'choose', TMessageType::REPLY, $result, $seqid, $output->isStrictWrite());
    }
    else
    {
      $output->writeMessageBegin('choose', TMessageType::REPLY, $seqid);
      $result->write($output);
      $output->writeMessageEnd();
      $output->getTransport()->flush();
    }
  }
  protected function process_batchChoose($seqid, $input, $output) {
    $args = new \com\boxalino\p13n\api\thrift\P13nService_batchChoose_args();
    $args->read($input);
    $input->readMessageEnd();
    $result = new \com\boxalino\p13n\api\thrift\P13nService_batchChoose_result();
    try {
      $result->success = $this->handler_->batchChoose($args->batchChoiceRequest);
    } catch (\com\boxalino\p13n\api\thrift\P13nServiceException $p13nServiceException) {
      $result->p13nServiceException = $p13nServiceException;
    }
    $bin_accel = ($output instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($output, 'batchChoose', TMessageType::REPLY, $result, $seqid, $output->isStrictWrite());
    }
    else
    {
      $output->writeMessageBegin('batchChoose', TMessageType::REPLY, $seqid);
      $result->write($output);
      $output->writeMessageEnd();
      $output->getTransport()->flush();
    }
  }
  protected function process_autocomplete($seqid, $input, $output) {
    $args = new \com\boxalino\p13n\api\thrift\P13nService_autocomplete_args();
    $args->read($input);
    $input->readMessageEnd();
    $result = new \com\boxalino\p13n\api\thrift\P13nService_autocomplete_result();
    try {
      $result->success = $this->handler_->autocomplete($args->request);
    } catch (\com\boxalino\p13n\api\thrift\P13nServiceException $p13nServiceException) {
      $result->p13nServiceException = $p13nServiceException;
    }
    $bin_accel = ($output instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($output, 'autocomplete', TMessageType::REPLY, $result, $seqid, $output->isStrictWrite());
    }
    else
    {
      $output->writeMessageBegin('autocomplete', TMessageType::REPLY, $seqid);
      $result->write($output);
      $output->writeMessageEnd();
      $output->getTransport()->flush();
    }
  }
  protected function process_autocompleteAll($seqid, $input, $output) {
    $args = new \com\boxalino\p13n\api\thrift\P13nService_autocompleteAll_args();
    $args->read($input);
    $input->readMessageEnd();
    $result = new \com\boxalino\p13n\api\thrift\P13nService_autocompleteAll_result();
    try {
      $result->success = $this->handler_->autocompleteAll($args->bundle);
    } catch (\com\boxalino\p13n\api\thrift\P13nServiceException $p13nServiceException) {
      $result->p13nServiceException = $p13nServiceException;
    }
    $bin_accel = ($output instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($output, 'autocompleteAll', TMessageType::REPLY, $result, $seqid, $output->isStrictWrite());
    }
    else
    {
      $output->writeMessageBegin('autocompleteAll', TMessageType::REPLY, $seqid);
      $result->write($output);
      $output->writeMessageEnd();
      $output->getTransport()->flush();
    }
  }
  protected function process_updateChoice($seqid, $input, $output) {
    $args = new \com\boxalino\p13n\api\thrift\P13nService_updateChoice_args();
    $args->read($input);
    $input->readMessageEnd();
    $result = new \com\boxalino\p13n\api\thrift\P13nService_updateChoice_result();
    try {
      $result->success = $this->handler_->updateChoice($args->choiceUpdateRequest);
    } catch (\com\boxalino\p13n\api\thrift\P13nServiceException $p13nServiceException) {
      $result->p13nServiceException = $p13nServiceException;
    }
    $bin_accel = ($output instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($output, 'updateChoice', TMessageType::REPLY, $result, $seqid, $output->isStrictWrite());
    }
    else
    {
      $output->writeMessageBegin('updateChoice', TMessageType::REPLY, $seqid);
      $result->write($output);
      $output->writeMessageEnd();
      $output->getTransport()->flush();
    }
  }
}