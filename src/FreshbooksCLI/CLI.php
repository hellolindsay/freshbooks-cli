<?php 
namespace FreshbooksCLI;
class CLI {
  
  public static function cli($argv) {
    $args = array_slice($argv,1);
    $op = array_shift($args);
    $op_method = 'op_'.$op;
    if (method_exists(get_called_class(),$op_method)) {
      call_user_func_array(get_called_class().'::'.$op_method,array($args));
    } else {
      CLI::op_usage($args);
    }
  }
  public static function op_usage() {
    print "USAGE: freshbooks [op] [arg1] [arg2]\n";
  }
  public static function op_me() {
  
    $api = CLI::get_freshbooks_api();
    $me = $api->get_user_me();
    $output = \FreshbooksCLI\Output::formatted_yaml($me);
    $output = \FreshbooksCLI\Output::border_box($output);
    print $output;
        
  }
  public static function op_call($args=array()) {
  
    $api = CLI::get_freshbooks_api();
    $clients = $api->make_call(current($args));
    $output = \FreshbooksCLI\Output::formatted_yaml($clients);
    $output = \FreshbooksCLI\Output::border_box($output);
    print $output;  
      
  }    
  public static function op_curl($args=array()) {
  
    $api = CLI::get_freshbooks_api();
    $clients = $api->fetch_response(current($args));
    $output = \FreshbooksCLI\Output::formatted_yaml($clients);
    $output = \FreshbooksCLI\Output::border_box($output);
    print $output;  
      
  }    
  public static function op_client($args=array()) {
  
    $api = CLI::get_freshbooks_api();
    $clients = $api->get_client($args);
    $output = \FreshbooksCLI\Output::formatted_yaml($clients);
    $output = \FreshbooksCLI\Output::border_box($output);
    print $output;  
      
  }    
  public static function op_clients() {
  
    $api = CLI::get_freshbooks_api();
    $clients = $api->get_clients();
    $output = \FreshbooksCLI\Output::formatted_yaml($clients);
    $output = \FreshbooksCLI\Output::border_box($output);
    print $output;  
      
  }  
  public static function op_project($args=array()) {
  
    $api = CLI::get_freshbooks_api();
    $projects = $api->get_project($args);
    $output = \FreshbooksCLI\Output::formatted_yaml($projects);
    $output = \FreshbooksCLI\Output::border_box($output);
    print $output;  
      
  }      
  public static function op_projects() {
  
    $api = CLI::get_freshbooks_api();
    $projects = $api->get_projects();
    $output = \FreshbooksCLI\Output::formatted_yaml($projects);
    $output = \FreshbooksCLI\Output::border_box($output);
    print $output;    

  }  
  public static function op_task($args=array()) {
  
    $api = CLI::get_freshbooks_api();
    $tasks = $api->get_task($args);
    $output = \FreshbooksCLI\Output::formatted_yaml($tasks);
    $output = \FreshbooksCLI\Output::border_box($output);
    print $output;  
      
  }     
  public static function op_tasks() {
  
    $api = CLI::get_freshbooks_api();
    $tasks = $api->get_tasks();
    $output = \FreshbooksCLI\Output::formatted_yaml($tasks);
    $output = \FreshbooksCLI\Output::border_box($output);
    print $output;    

  }    
  public static function op_times() {
  
    $api = CLI::get_freshbooks_api();
    $time_entries = $api->get_time_entries();
    $output = \FreshbooksCLI\Output::formatted_yaml($time_entries);
    $output = \FreshbooksCLI\Output::border_box($output);
    print $output;    

  }    
  public static function op_invoices() {

    $api = CLI::get_freshbooks_api();
    $invoices = $api->get_invoices();
    $output = \FreshbooksCLI\Output::formatted_yaml($invoices);
    //$output = \FreshbooksCLI\Output::border_box($output);
    print $output;    

  }      
  public static function get_freshbooks_api() {
    
    // load config values
    $config = \FreshbooksCLI\JsonConfig::config_get('freshbooks-config');
    $app = @$config['app'] ?: array();
    $oauth = @$config['oauth'] ?: array();
    
    // get and return api object
    $api = new \FreshbooksAPI\Client($app,$oauth);
    return $api;
    
  }
  public static function op_connect() {

    // get api object
    $api = CLI::get_freshbooks_api();
    $api->connect();
    $oauth = $api->get_oauth_values();
    
    // get access token
    $access_token = @$oauth['access_token'] ?: null;
    if (empty($access_token)) throw new \Exception("Unable to connect to Freshbooks API");
    
    // save oauth values to config file
    \FreshbooksCLI\JsonConfig::config_set('freshbooks-config',array(
      'oauth'=>$oauth
    ));
    
    // display config
    $config = \FreshbooksCLI\JsonConfig::config_get('freshbooks-config');
    
    // output
    print "Connected: ";
    print_r($config);

  }
  public static function op_config() {
    
    // ask for app details
    $client_id = readline("Enter Client ID (enter to skip): ");
    $client_secret = readline("Enter Client Secret (enter to skip): ");
    $redirect_uri = readline("Enter Redirect URI (enter to skip): ");
        
    // use current settings if something was left blank
    $current = \FreshbooksCLI\JsonConfig::config_get('freshbooks-config');
    if (empty($client_id) && !empty($current['app']['client_id']))
      $client_id = $current['app']['client_id'];
    if (empty($client_secret) && !empty($current['app']['client_secret']))
      $client_secret = $current['app']['client_secret'];
    if (empty($redirect_uri) && !empty($current['app']['redirect_uri']))
      $redirect_uri = $current['app']['redirect_uri'];
      
    // save config file
    \FreshbooksCLI\JsonConfig::config_set('freshbooks-config',array(
      'app'=>array(
        'client_id'=>$client_id,
        'client_secret'=>$client_secret,
        'redirect_uri'=>$redirect_uri,
      ),
    ));

    // ask user to visit auth url and copy auth code
    $api = CLI::get_freshbooks_api();
    $auth_url = $api->get_auth_url();
    print "Please visit the following URL, log in, and then copy the 'code' parameter from the url:\n\n";
    print "$auth_url\n\n";    
    $auth_code = readline("Enter Authorization Code (enter to skip): ");
    
    // use current auth code if it was left blank
    if (empty($auth_code) && !empty($current['oauth']['auth_code']))
      $auth_code = $current['oauth']['auth_code'];
      
    // save config file
    \FreshbooksCLI\JsonConfig::config_set('freshbooks-config',array(
      'oauth'=>array(
        'auth_code'=>$auth_code,
      ),
    ));

    // display saved data
    $saved = \FreshbooksCLI\JsonConfig::config_get('freshbooks-config');
    print 'freshbooks-config.json: '; 
    print_r($saved);

  }
  
}