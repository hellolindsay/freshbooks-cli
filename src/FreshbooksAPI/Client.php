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
  public function fetch_response($url=null) {
    
    // get access token
    $access_token = @$this->oauth['access_token'] ?: null;
    if (empty($access_token)) throw new \Exception("No access token set");

    // prepend api location if :// not in url
    if (!preg_match('/\:\/\//',$url)) {
      $url = 'https://api.freshbooks.com/'.ltrim($url,'/');
    }
    
    // replace account id and business id if in url
    if (preg_match('/account_id/',$url) || preg_match('/business_id/',$url)) {
      $me = Client::get_user_me();
      $account_id = $me['response']['roles'][0]['accountid'];
      $business_id = $me['response']['business_memberships'][0]['business']['id'];  
      $url = strtr($url,array(
        'account_id' => $account_id,
        'business_id' => $business_id,
      ));      
    }

    // make users/clients call
    $response = \FreshbooksAPI\Curl::curl_request('GET',$url,
      // data
      null,
      // headers
      array(
        "Authorization: Bearer ".$access_token,
        "Api-Version: alpha",
        "Content-Type: Accept",
    ));  
    return $response;
  }    
  public function make_call($url=null) {  
    $response = Client::fetch_response($url);
    $decoded = json_decode($response['response']['content'],TRUE);
    return $decoded;
  }
  public function get_user_me() {

    // make users/me call
    $decoded = Client::make_call("auth/api/v1/users/me");
    if (!empty($response['error'])) throw new \Exception(print_r($decoded,TRUE));
    return $decoded;
    
  }    
  public function get_client($keys=array()) {
    $clients = Client::get_clients();
    $selected = array();
    foreach($clients as $client) {
      foreach($keys as $key) {
        if ($client['key']==$key) {
          $selected[$key] = $client;
        }
      }
    }
    return $selected;
  }
  public function get_clients() {
    $clients_list = array();
    if (empty($clients_list)) {
    
      // make call
      $decoded = Client::make_call("accounting/account/account_id/users/clients");
      if (!empty($response['error'])) throw new \Exception(print_r($decoded,TRUE));

      // format clients list
      if (is_array($decoded['response']['result']['clients'])) {
        foreach($decoded['response']['result']['clients'] as $client) {
          $key = strtolower(preg_replace('/[^a-z0-9]/i','',$client["organization"]));
          $clients_list[ $key ] = array(
            'key' => $key,
            'label' => $client["organization"],
            'id' => $client["id"],
            'organization' => $client["organization"],
            'shortname' => $client["organization"],
            'fullname' => $client["organization"],
            'contact' => $client["fname"].' '.$client["lname"],
            'address' => $client["p_street"].', '.$client["p_city"].', '.$client["p_province"].', '.$client["p_code"]
            //'data' => $client,
          );
        }
      } else {
        throw new \Exception(print_r($decoded,TRUE));
      }
      
    }
    return $clients_list;
    
  }  
  public function get_project($keys=array()) {
    $projects = Client::get_projects();
    $selected = array();
    foreach($projects as $project) {
      foreach($keys as $key) {
        if ($project['key']==$key) {
          $selected[$key] = $project;
        }
      }
    }
    return $selected;
  }
  public function get_projects() {
    static $projects_list = array();
    if (empty($projects_list)) {

      // make call
      $decoded = Client::make_call("projects/business/business_id/projects");
      if (!empty($response['error'])) throw new \Exception(print_r($decoded,TRUE));
      
      // format projects list
      if (is_array($decoded['projects'])) {
        foreach($decoded['projects'] as $project) {
          $key = strtolower(preg_replace('/[^a-z0-9]/i','',$project["title"]));
          $unique_key = $key.'-'.$project["client_id"];
          $projects_list[$unique_key] = array(
            'key'=>$key,
            'unique'=>$unique_key,
            'label'=>$project["title"],
            'id'=>$project["id"],
            'title'=>$project["title"],
            'client_id'=>$project["client_id"],
          );
        }
      } else {
        throw new \Exception(print_r($decoded,TRUE));
      }
      
    }
    return $projects_list;
  }    
  public function get_task($keys=array()) {
    $tasks = Client::get_tasks();
    $selected = array();
    foreach($tasks as $task) {
      foreach($keys as $key) {
        if ($task['key']==$key) {
          $selected[$key] = $task;
        }
      }
    }
    return $selected;
  }  
  public function get_tasks() {

    // make call
    $decoded = Client::make_call("accounting/account/account_id/projects/tasks");
    if (!empty($response['error'])) throw new \Exception(print_r($decoded,TRUE));
    
    // format tasks list
    $tasks_list = array();
    if (is_array($decoded['response']['result']['tasks'])) {
      foreach($decoded['response']['result']['tasks'] as $task) {
        $key = strtolower(preg_replace('/[^a-z0-9]/i','',$task["tname"]));
        $tasks_list[$key] = array(
          'key'=>$key,
          'label'=>$task["tname"],
          'id'=>$task["id"],
          'tname'=>$task["tname"],
        ); 
      }
    } else {
      throw new \Exception(print_r($decoded,TRUE));
    }
    return $tasks_list;
  }      
  public function get_time_entries($options=array()) {
    
    // prepare params
    $params = array();
    //if (!empty($options['since'])) $params[] = "started_from=".date('Y-m-d\TH:i:s.000\z',$options['since']);
    //if (!empty($options['until'])) $params[] = "started_to=".date('Y-m-d\TH:i:s.000\z',$options['until']);;
    //if (!empty($options['client'])) $params[] = "client_id=".$clients[$options['client']]['id'];

    // make call
    $decoded = Client::make_call("timetracking/business/business_id/time_entries?$params");
    if (!empty($response['error'])) throw new \Exception(print_r($decoded,TRUE));

    // format time entries
    $time_entries_list = array();
    if (is_array($decoded['time_entries'])) {
      foreach($decoded['time_entries'] as $time) {
        $date = current(explode('T',$time["started_at"]));
        $timestamp = strtotime($time["started_at"]);
        $started_at = date('Y-m-d H:i:s',$timestamp);
        $start_date = date('Y-m-d',$timestamp);
        $start_time = date('H:i',$timestamp);
        $hours = round($time["duration"] / 60.0 / 60.0,2);
        $title = current(explode('.',$time["note"],2));
        $note = next(explode('.',$time["note"],2));
        // $client = Client::get_client(array('id'=>$time['client_id']));
        // $project = Client::get_project(array('id'=>$time['project_id']));
        // $task = Client::get_task(array('id'=>$time['task_id']));
        $row = array();
        $row['date'] = $start_date;
        $row['time'] = $start_time;
        $row['hours'] = $hours;
        $row['title'] = $title;
        $row['client'] = $client['organization'];
        $row['project'] = $project['title'];
        $row['task'] = $task['tname'];
        //$row['note'] = $note;
        $time_entries_list[$timestamp.'-'.microtime()] = $row; //$date.' '.$title.' ('.$hours.'h) ('.$time["id"].')';
      }
    } else {
      throw new \Exception(print_r($decoded,TRUE));
    }
    
    // return list
    ksort($time_entries_list);
    $time_entries_list = array_values($time_entries_list);
    return $time_entries_list;
    
  }  
  public function get_invoices() {

    // make call
    $decoded = Client::make_call("accounting/account/account_id/invoices/invoices");
    if (!empty($response['error'])) throw new \Exception(print_r($decoded,TRUE));
    
    // format invoices list
    $invoices_list = array();
    if (is_array($decoded['response']['result']['invoices'])) {
      foreach($decoded['response']['result']['invoices'] as $invoice) {
        $key = strtolower(preg_replace('/[^a-z0-9]/i','',$invoice["tname"]));
        $invoices_list[] = $invoice;
        // $invoices_list[$key] = array(
        //   'key'=>$key,
        //   'label'=>$invoice["tname"],
        //   'id'=>$invoice["id"],
        //   'tname'=>$invoice["tname"],
        // ); 
      }
    } else {
      throw new \Exception(print_r($decoded,TRUE));
    }
    return $invoices_list;
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