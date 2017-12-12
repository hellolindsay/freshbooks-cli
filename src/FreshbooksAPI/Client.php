<?php
namespace FreshbooksAPI;
class Client {
  
  private $app = array();
  private $oauth = array();
  
  public function __construct($app,$oauth=array()) {
    
    // store app and oauth details
    $this->app = @$app;
    $this->oauth = @$oauth;    
    
  }
  public function get_auth_url() {
    
    // load config
    $client_id = $this->app['client_id'];
    $redirect_uri = $this->app['redirect_uri'];
    if (empty($client_id)) throw new \Exception("No client_id set.");
    if (empty($redirect_uri)) throw new \Exception("No redirect_uri set.");
    
    // assemble auth url
    $auth_url = "https://my.freshbooks.com/service/auth/oauth/authorize?client_id=$client_id&response_type=code&redirect_uri=$redirect_uri";
    return $auth_url;

  }  
  public function get_oauth_values() {
  
    return $this->oauth;
      
  }
  public function get_user_me() {
  
    // get access token
    $access_token = @$this->oauth['access_token'] ?: null;
    if (empty($access_token)) throw new \Exception("No access token set");

    // make users/me call
    $response = \FreshbooksAPI\Curl::curl_request('GET',"https://api.freshbooks.com/auth/api/v1/users/me",
      // data
      null,
      // headers
      array(
        "Authorization: Bearer ".$access_token,
        "Api-Version: alpha",
        "Content-Type: application/json",
      )
    );  
    
    // return decoded response
    $decoded = json_decode($response['response']['content'],TRUE);
    if (!empty($response['error'])) throw new \Exception(print_r($decoded,TRUE));
    
    // return decoded data
    return $decoded;
    
  }    
  public function connect() {
    
    // check if already connected
    if ($this->is_connected()) return;
      
    // get auth code and refresh token
    $auth_code = $this->oauth['auth_code'];
    $refresh_token = $this->oauth['refresh_token'];
    if (empty($auth_code) && empty($refresh_token)) {
      throw new \Exception("No auth_code or refresh_token set");
    }
    
    // connect with refresh token or auth code
    $oauth = array();
    if (!empty($refresh_token)) {
      try {
        $oauth = $this->connect_with_refresh_token();
      } 
      catch(Exception $e) {}
    }
    else if (!empty($auth_code)) {
      try {
        $oauth = $this->connect_with_auth_code();
      } 
      catch(Exception $e) {}
    }
    
    // get access token
    $access_token = @$oauth['access_token'] ?: null;
    if (empty($access_token)) throw new \Exception("Unable to connect, please get a new auth_code and try again");

    // set and return oauth values
    $this->oauth = $oauth;
    return $oauth;
    
  }
  public function is_connected() {
    
    // get access token
    $access_token = @$this->oauth['access_token'] ?: null;
    if (empty($access_token)) return FALSE;

    // make users/me call
    $response = \FreshbooksAPI\Curl::curl_request('GET',"https://api.freshbooks.com/auth/api/v1/users/me",
      // data
      null,
      // headers
      array(
        "Authorization: Bearer ".$access_token,
        "Api-Version: alpha",
        "Content-Type: application/json",
      )
    );  
    
    // return decoded response
    $decoded = json_decode($response['response']['content'],TRUE);
    if (!empty($response['error'])) return FALSE;
    if (empty($response['response']['id'])) return FALSE;
    return TRUE;
    
  }
  public function connect_with_auth_code() {
    
    // get app values
    $client_id = $this->app['client_id'];
    $client_secret = $this->app['client_secret'];
    $redirect_uri = $this->app['redirect_uri'];
    if (empty($client_id)) throw new \Exception("No client_id set.");
    if (empty($client_secret)) throw new \Exception("No client_secret set.");
    if (empty($redirect_uri)) throw new \Exception("No redirect_uri set.");

    // get auth code
    $auth_code = $this->oauth['auth_code'];
    if (empty($auth_code)) throw new \Exception("No auth_code set.");
    
    // call data
    $data = array(
      'grant_type'    => 'authorization_code',
      'client_secret' => $client_secret,
      'code'          => $auth_code,
      'client_id'     => $client_id,
      'redirect_uri'  => $redirect_uri,
    );
        
    // make oauth token call
    $response = \FreshbooksAPI\Curl::curl_request('POST',"https://api.freshbooks.com/auth/oauth/token",
      // data
      strtr(json_encode($data),array('\/'=>'/')), 
      // headers
      array(
        "Api-Version: alpha",
        "Content-Type: application/json",
      )
    );
    
    // check for access token
    $decoded = json_decode($response['response']['content'],TRUE);
    $access_token = @$decoded['access_token'] ?: null;
    if (empty($access_token)) throw new \Exception(print_r($decoded,TRUE));
    
    // set and return oauth values
    $this->oauth = $decoded;
    return $this->oauth;
    
  }
  public function connect_with_refresh_token() {
    
    // get app values
    $client_id = $this->app['client_id'];
    $client_secret = $this->app['client_secret'];
    $redirect_uri = $this->app['redirect_uri'];
    if (empty($client_id)) throw new \Exception("No client_id set.");
    if (empty($client_secret)) throw new \Exception("No client_secret set.");
    if (empty($redirect_uri)) throw new \Exception("No redirect_uri set.");
    
    // get oauth values
    $refresh_token = $this->oauth['refresh_token'];
    if (empty($refresh_token)) throw new \Exception("No refresh_token set.");
    
    // make oauth token call
    $response = \FreshbooksAPI\Curl::curl_request('POST',"https://api.freshbooks.com/auth/oauth/token",
      // data
      strtr(json_encode(array(
        'grant_type'    => 'refresh_token',
        'client_secret' => $client_secret,
        'refresh_token' => $refresh_token,
        'client_id'     => $client_id,
        'redirect_uri'  => $redirect_uri,
      )),array('\/'=>'/')), 
      // headers
      array(
        "Api-Version: alpha",
        "Content-Type: application/json",
      )
    );

    // check for access token
    $decoded = json_decode($response['response']['content'],TRUE);
    $access_token = @$decoded['access_token'] ?: null;
    if (empty($access_token)) throw new \Exception(print_r($decoded,TRUE));
    
    // set and return oauth values
    $this->oauth = $decoded;
    return $this->oauth;

  }  

}