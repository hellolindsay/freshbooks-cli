<?php 
namespace FreshbooksCLI;

class Client {
  
  public static function cli($argv) {
    
    \FreshbooksAPI\Client::connect();
    
    print "CLI: ";
    print_r($argv);
    
  }
  
}