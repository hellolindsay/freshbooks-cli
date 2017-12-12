<?php
namespace FreshbooksAPI;
class Curl {

  // protected
  public function curl_request($method,$url,$data=array(),$headers=array(),$auth=null,$timeout=4) {
  	$curl_request = Curl::curl_build_request($method,$url,$data,$headers,$auth);
  	$curl_response = Curl::curl_fetch_response($curl_request,$timeout);
      return $curl_response;
  }
  // protected
  public function curl_build_request($method,$url,$data=array(),$headers=array(),$auth=null) {
  	$request_opt_array = array();
    // set url to connect to
  	$request_opt_array[CURLOPT_URL] = $url; 
    // use post method
    if (strtoupper($method)=='POST')
  	  $request_opt_array[CURLOPT_CUSTOMREQUEST] = 'POST'; 
    // use post method
  	if (strtoupper($method)=='POST') 
  	  $request_opt_array[CURLOPT_POST] = 1; 
    // set post data
  	if (strtoupper($method)=='POST')
  	  $request_opt_array[CURLOPT_POSTFIELDS] = $data; // set post data
    // set auth 
    if (!empty($auth)) 
      $request_opt_array[CURLOPT_USERPWD] = $auth;
    // add custom headers to http request
    if (!empty($headers)) 
      $request_opt_array[CURLOPT_HTTPHEADER] = $headers; 
    // return array
    return $request_opt_array;
  }
  // protected
  public function curl_fetch_response($request_opt_array,$timeout=4) {
  	// override some options
  	$request_opt_array[CURLOPT_CONNECTTIMEOUT] = $timeout; // set timeout for connection
  	$request_opt_array[CURLOPT_TIMEOUT] = $timeout; // set timeout for response
  	$request_opt_array[CURLOPT_HEADER] = 1; // yes, return headers
  	$request_opt_array[CURLOPT_RETURNTRANSFER] = 1; // yes, return url contents
  	$request_opt_array[CURLINFO_HEADER_OUT] = 1; // allow us to get a copy of the HTTP request
    $curl = curl_init();
    curl_setopt_array($curl,$request_opt_array); // set options for this request
    // get curl response
    $http_response = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE); // get http status
    $http_request = curl_getinfo($curl, CURLINFO_HEADER_OUT);  // get a copy of the sent request
    $http_request_url = $request_opt_array[CURLOPT_URL];
    $http_request_post_data = !empty($request_opt_array[CURLOPT_POSTFIELDS]) ? $request_opt_array[CURLOPT_POSTFIELDS] : array();
	  // close connection
    curl_close ($curl);
    // build return data
    $return_data = array();
    $return_data['response'] = array();
  	$return_data['response']['status'] = $http_status;
    $return_data['response']['content'] = Curl::_curl_parse_content($http_response);
    $return_data['response']['headers'] = Curl::_curl_parse_headers($http_response);
    $return_data['response']['raw'] = $http_response;
    $return_data['request'] = array();
    $return_data['request']['url'] = $http_request_url;
    $return_data['request']['post_data'] = $http_request_post_data;
    $return_data['request']['raw'] = $http_request;
  	// return reponse array
  	return $return_data;
  } 
  // _private
  private function _curl_parse_content($http_string) {
    // break response into parts (break on double \r\n)
    $partbroken = explode("\r\n\r\n",$http_string);   
    // loop through parts and remove headers
    foreach($partbroken as $k=>$part) {
      // if this section starts wth "HTTP/" 
      if (substr(trim($part),0,5)=='HTTP/') {
        // unset this part, it's a header section
        unset($partbroken[$k]);
        // continue to next item
        // yes, there are sometimes multiple header sections
        continue;
      } 
      // if this part is not a header section, then we are done, break 
      // if we continue looping, we might damage the content
      break; 
    }
    // rejoin remaining parts into a string (they're all part of the content)
    $http_content_string = implode("\r\n",$partbroken);
  	// return response content
  	return $http_content_string;
  } 
  // _private
  private function _curl_parse_headers($http_string) {
  	// break response into lines
  	$linebroken = explode("\r\n",$http_string);
  	// remove HTTP status codes from the begining of the reponnse
  	while( !empty($linebroken) && ( trim($linebroken[0])=='' || substr($linebroken[0],0,5)=='HTTP/')) { array_shift($linebroken); }
  	// join lines back together
  	$http_string_no_status = implode("\r\n",$linebroken);	
      // split headers and response
  	$doublelinebroken = explode("\r\n\r\n",$http_string_no_status,2);
  	// get headers from broken string
  	$headers = trim($doublelinebroken[0]);
  	// break headers into lines
  	$headers_linebroken = explode("\r\n",$headers);
  	// array that will hold parsed headers
  	$headers_array = array();
  	// loop through each line and find key value pairs
  	foreach($headers_linebroken as $line) {
  		// if this line is empty, skip it
  		if (trim($line)=='') continue;
  		// explode line at the first colon
  		$colonbroken = explode(':',$line,2);
  		// get key and value from line
  		$key = trim($colonbroken[0]);
  		$value = trim($colonbroken[1]);
  		// add key and value to the parsed array
  		$headers_array[$key] = $value;
  	}
  	// return parsed headers
  	return $headers_array;
  }

}