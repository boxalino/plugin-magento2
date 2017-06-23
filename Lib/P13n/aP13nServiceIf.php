<?php

namespace com\boxalino\p13n\api\thrift;

interface P13nServiceIf {
  /**
   * <dl>
   * <dt>@param choiceRequest</dt>
   * <dd>the ChoiceRequest object containing your request</dd>
   * 
   * <dt>@return</dt>
   * <dd>a ChoiceResponse object containing the list of variants</dd>
   * 
   * <dt>@throws P13nServiceException</dt>
   * <dd>an exception containing an error message</dd>
   * </dl>
   * 
   * @param \com\boxalino\p13n\api\thrift\ChoiceRequest $choiceRequest
   * @return \com\boxalino\p13n\api\thrift\ChoiceResponse list of personalized variants. Item's index corresponds to the index of the
   * ChoiceInquiry
   * 
   * @throws \com\boxalino\p13n\api\thrift\P13nServiceException
   */
  public function choose(\com\boxalino\p13n\api\thrift\ChoiceRequest $choiceRequest);
  /**
   * <dl>
   * <dt>@param batchChoiceRequest</dt>
   * <dd>the BatchChoiceRequest object containing your requests</dd>
   * 
   * <dt>@return</dt>
   * <dd>a BatchChoiceResponse object containing the list of variants for each request</dd>
   * 
   * <dt>@throws P13nServiceException</dt>
   * <dd>an exception containing an error message</dd>
   * </dl>
   * 
   * @param \com\boxalino\p13n\api\thrift\BatchChoiceRequest $batchChoiceRequest
   * @return \com\boxalino\p13n\api\thrift\BatchChoiceResponse <dl>
   * <dt>variants</dt>
   * <dd><b>deprecated</b> - contains non-null value only if
   * corresponding BatchChoiceRequest had only one ChoiceInquiry</dd>
   * 
   * <dt>selectedVariants</dt>
   * <dd>outer list corresponds to profileIds given in BatchChoiceRequest, while
   * inner list corresponds to list of ChoiceInquiries from BatchChoiceRequest</dd>
   * </dl>
   * 
   * @throws \com\boxalino\p13n\api\thrift\P13nServiceException
   */
  public function batchChoose(\com\boxalino\p13n\api\thrift\BatchChoiceRequest $batchChoiceRequest);
  /**
   * <dl>
   * <dt>@param request</dt>
   * <dd>the AutocompleteRequest object containing your request</dd>
   * 
   * <dt>@return</dt>
   * <dd>a AutocompleteResponse object containing the list of hits</dd>
   * 
   * <dt>@throws P13nServiceException</dt>
   * <dd>an exception containing an error message</dd>
   * </dl>
   * 
   * @param \com\boxalino\p13n\api\thrift\AutocompleteRequest $request
   * @return \com\boxalino\p13n\api\thrift\AutocompleteResponse
   * @throws \com\boxalino\p13n\api\thrift\P13nServiceException
   */
  public function autocomplete(\com\boxalino\p13n\api\thrift\AutocompleteRequest $request);
  /**
   * @param \com\boxalino\p13n\api\thrift\AutocompleteRequestBundle $bundle
   * @return \com\boxalino\p13n\api\thrift\AutocompleteResponseBundle
   * @throws \com\boxalino\p13n\api\thrift\P13nServiceException
   */
  public function autocompleteAll(\com\boxalino\p13n\api\thrift\AutocompleteRequestBundle $bundle);
  /**
   * Updating a choice or creating a new choice if choiceId is not given in choiceUpdateRequest.
   * 
   * @param \com\boxalino\p13n\api\thrift\ChoiceUpdateRequest $choiceUpdateRequest
   * @return \com\boxalino\p13n\api\thrift\ChoiceUpdateResponse Server response for one ChoiceUpdateRequest
   * 
   * @throws \com\boxalino\p13n\api\thrift\P13nServiceException
   */
  public function updateChoice(\com\boxalino\p13n\api\thrift\ChoiceUpdateRequest $choiceUpdateRequest);
}