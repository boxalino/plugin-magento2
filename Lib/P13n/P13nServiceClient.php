<?php

namespace com\boxalino\p13n\api\thrift;

use Thrift\Protocol\TBinaryProtocolAccelerated;
use Thrift\Type\TMessageType;
use Thrift\Exception\TApplicationException;
use com\boxalino\p13n\api\thrift\P13nServiceException;
class P13nServiceClient implements \com\boxalino\p13n\api\thrift\P13nServiceIf {
  protected $input_ = null;
  protected $output_ = null;

  protected $seqid_ = 0;

  public function __construct($input, $output=null) {
    $this->input_ = $input;
    $this->output_ = $output ? $output : $input;
  }

  public function choose(\com\boxalino\p13n\api\thrift\ChoiceRequest $choiceRequest)
  {
    $this->send_choose($choiceRequest);
    return $this->recv_choose();
  }

  public function send_choose(\com\boxalino\p13n\api\thrift\ChoiceRequest $choiceRequest)
  {
    $args = new \com\boxalino\p13n\api\thrift\P13nService_choose_args();
    $args->choiceRequest = $choiceRequest;
    $bin_accel = ($this->output_ instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($this->output_, 'choose', TMessageType::CALL, $args, $this->seqid_, $this->output_->isStrictWrite());
    }
    else
    {
      $this->output_->writeMessageBegin('choose', TMessageType::CALL, $this->seqid_);
      $args->write($this->output_);
      $this->output_->writeMessageEnd();
      $this->output_->getTransport()->flush();
    }
  }

    /**
     * @return ChoiceResponse|mixed
     * @throws TApplicationException
     * @throws \Exception
     * @throws \com\boxalino\p13n\api\thrift\P13nServiceException
     */
  public function recv_choose()
  {
    $bin_accel = ($this->input_ instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_read_binary');
    if ($bin_accel) $result = thrift_protocol_read_binary($this->input_, '\com\boxalino\p13n\api\thrift\P13nService_choose_result', $this->input_->isStrictRead());
    else
    {
      $rseqid = 0;
      $fname = null;
      $mtype = 0;

      $this->input_->readMessageBegin($fname, $mtype, $rseqid);
      if ($mtype == TMessageType::EXCEPTION) {
        $x = new TApplicationException();
        $x->read($this->input_);
        $this->input_->readMessageEnd();
        throw $x;
      }
      $result = new \com\boxalino\p13n\api\thrift\P13nService_choose_result();
      $result->read($this->input_);
      $this->input_->readMessageEnd();
    }
    if ($result->success !== null) {
      return $result->success;
    }
    if ($result->p13nServiceException !== null) {
        $p13nServiceException = new P13nServiceException();
        $p13nServiceException = $result->p13nServiceException;
        throw $p13nServiceException;
    }
    throw new \Exception("choose failed: unknown result");
  }

  public function batchChoose(\com\boxalino\p13n\api\thrift\BatchChoiceRequest $batchChoiceRequest)
  {
    $this->send_batchChoose($batchChoiceRequest);
    return $this->recv_batchChoose();
  }

  public function send_batchChoose(\com\boxalino\p13n\api\thrift\BatchChoiceRequest $batchChoiceRequest)
  {
    $args = new \com\boxalino\p13n\api\thrift\P13nService_batchChoose_args();
    $args->batchChoiceRequest = $batchChoiceRequest;
    $bin_accel = ($this->output_ instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($this->output_, 'batchChoose', TMessageType::CALL, $args, $this->seqid_, $this->output_->isStrictWrite());
    }
    else
    {
      $this->output_->writeMessageBegin('batchChoose', TMessageType::CALL, $this->seqid_);
      $args->write($this->output_);
      $this->output_->writeMessageEnd();
      $this->output_->getTransport()->flush();
    }
  }

  public function recv_batchChoose()
  {
    $bin_accel = ($this->input_ instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_read_binary');
    if ($bin_accel) $result = thrift_protocol_read_binary($this->input_, '\com\boxalino\p13n\api\thrift\P13nService_batchChoose_result', $this->input_->isStrictRead());
    else
    {
      $rseqid = 0;
      $fname = null;
      $mtype = 0;

      $this->input_->readMessageBegin($fname, $mtype, $rseqid);
      if ($mtype == TMessageType::EXCEPTION) {
        $x = new TApplicationException();
        $x->read($this->input_);
        $this->input_->readMessageEnd();
        throw $x;
      }
      $result = new \com\boxalino\p13n\api\thrift\P13nService_batchChoose_result();
      $result->read($this->input_);
      $this->input_->readMessageEnd();
    }
    if ($result->success !== null) {
      return $result->success;
    }
    if ($result->p13nServiceException !== null) {
        $p13nServiceException = new P13nServiceException();
        $p13nServiceException = $result->p13nServiceException;
        throw $p13nServiceException;
    }
    throw new \Exception("batchChoose failed: unknown result");
  }

  public function autocomplete(\com\boxalino\p13n\api\thrift\AutocompleteRequest $request)
  {
    $this->send_autocomplete($request);
    return $this->recv_autocomplete();
  }

  public function send_autocomplete(\com\boxalino\p13n\api\thrift\AutocompleteRequest $request)
  {
    $args = new \com\boxalino\p13n\api\thrift\P13nService_autocomplete_args();
    $args->request = $request;
    $bin_accel = ($this->output_ instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($this->output_, 'autocomplete', TMessageType::CALL, $args, $this->seqid_, $this->output_->isStrictWrite());
    }
    else
    {
      $this->output_->writeMessageBegin('autocomplete', TMessageType::CALL, $this->seqid_);
      $args->write($this->output_);
      $this->output_->writeMessageEnd();
      $this->output_->getTransport()->flush();
    }
  }

  public function recv_autocomplete()
  {
    $bin_accel = ($this->input_ instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_read_binary');
    if ($bin_accel) $result = thrift_protocol_read_binary($this->input_, '\com\boxalino\p13n\api\thrift\P13nService_autocomplete_result', $this->input_->isStrictRead());
    else
    {
      $rseqid = 0;
      $fname = null;
      $mtype = 0;

      $this->input_->readMessageBegin($fname, $mtype, $rseqid);
      if ($mtype == TMessageType::EXCEPTION) {
        $x = new TApplicationException();
        $x->read($this->input_);
        $this->input_->readMessageEnd();
        throw $x;
      }
      $result = new \com\boxalino\p13n\api\thrift\P13nService_autocomplete_result();
      $result->read($this->input_);
      $this->input_->readMessageEnd();
    }
    if ($result->success !== null) {
      return $result->success;
    }
    if ($result->p13nServiceException !== null) {
        $p13nServiceException = new P13nServiceException();
        $p13nServiceException = $result->p13nServiceException;
        throw $p13nServiceException;
    }
    throw new \Exception("autocomplete failed: unknown result");
  }

  public function autocompleteAll(\com\boxalino\p13n\api\thrift\AutocompleteRequestBundle $bundle)
  {
    $this->send_autocompleteAll($bundle);
    return $this->recv_autocompleteAll();
  }

  public function send_autocompleteAll(\com\boxalino\p13n\api\thrift\AutocompleteRequestBundle $bundle)
  {
    $args = new \com\boxalino\p13n\api\thrift\P13nService_autocompleteAll_args();
    $args->bundle = $bundle;
    $bin_accel = ($this->output_ instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($this->output_, 'autocompleteAll', TMessageType::CALL, $args, $this->seqid_, $this->output_->isStrictWrite());
    }
    else
    {
      $this->output_->writeMessageBegin('autocompleteAll', TMessageType::CALL, $this->seqid_);
      $args->write($this->output_);
      $this->output_->writeMessageEnd();
      $this->output_->getTransport()->flush();
    }
  }

  public function recv_autocompleteAll()
  {
    $bin_accel = ($this->input_ instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_read_binary');
    if ($bin_accel) $result = thrift_protocol_read_binary($this->input_, '\com\boxalino\p13n\api\thrift\P13nService_autocompleteAll_result', $this->input_->isStrictRead());
    else
    {
      $rseqid = 0;
      $fname = null;
      $mtype = 0;

      $this->input_->readMessageBegin($fname, $mtype, $rseqid);
      if ($mtype == TMessageType::EXCEPTION) {
        $x = new TApplicationException();
        $x->read($this->input_);
        $this->input_->readMessageEnd();
        throw $x;
      }
      $result = new \com\boxalino\p13n\api\thrift\P13nService_autocompleteAll_result();
      $result->read($this->input_);
      $this->input_->readMessageEnd();
    }
    if ($result->success !== null) {
      return $result->success;
    }
    if ($result->p13nServiceException !== null) {
        $p13nServiceException = new P13nServiceException();
        $p13nServiceException = $result->p13nServiceException;
        throw $p13nServiceException;
    }
    throw new \Exception("autocompleteAll failed: unknown result");
  }

  public function updateChoice(\com\boxalino\p13n\api\thrift\ChoiceUpdateRequest $choiceUpdateRequest)
  {
    $this->send_updateChoice($choiceUpdateRequest);
    return $this->recv_updateChoice();
  }

  public function send_updateChoice(\com\boxalino\p13n\api\thrift\ChoiceUpdateRequest $choiceUpdateRequest)
  {
    $args = new \com\boxalino\p13n\api\thrift\P13nService_updateChoice_args();
    $args->choiceUpdateRequest = $choiceUpdateRequest;
    $bin_accel = ($this->output_ instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($this->output_, 'updateChoice', TMessageType::CALL, $args, $this->seqid_, $this->output_->isStrictWrite());
    }
    else
    {
      $this->output_->writeMessageBegin('updateChoice', TMessageType::CALL, $this->seqid_);
      $args->write($this->output_);
      $this->output_->writeMessageEnd();
      $this->output_->getTransport()->flush();
    }
  }

  public function recv_updateChoice()
  {
    $bin_accel = ($this->input_ instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_read_binary');
    if ($bin_accel) $result = thrift_protocol_read_binary($this->input_, '\com\boxalino\p13n\api\thrift\P13nService_updateChoice_result', $this->input_->isStrictRead());
    else
    {
      $rseqid = 0;
      $fname = null;
      $mtype = 0;

      $this->input_->readMessageBegin($fname, $mtype, $rseqid);
      if ($mtype == TMessageType::EXCEPTION) {
        $x = new TApplicationException();
        $x->read($this->input_);
        $this->input_->readMessageEnd();
        throw $x;
      }
      $result = new \com\boxalino\p13n\api\thrift\P13nService_updateChoice_result();
      $result->read($this->input_);
      $this->input_->readMessageEnd();
    }
    if ($result->success !== null) {
      return $result->success;
    }
    if ($result->p13nServiceException !== null) {
        $p13nServiceException = new P13nServiceException();
        $p13nServiceException = $result->p13nServiceException;
        throw $p13nServiceException;
    }
    throw new \Exception("updateChoice failed: unknown result");
  }

}