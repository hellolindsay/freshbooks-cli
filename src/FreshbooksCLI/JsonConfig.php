<?php
namespace FreshbooksCLI;
class JsonConfig {

    public function root() {
      $script_location = $_SERVER['SCRIPT_FILENAME'];
      $script_location = realpath($script_location);
      $script_location = dirname($script_location);
      return $script_location;
    }
    public function config_overwrite($name,$write) {
      $filename = JsonConfig::root()."/$name.json";
      $json = is_array($write) ? $write : json_decode($write,TRUE);
      if (!is_array($json)) die("Invalid JSON: $write\n");
      $pretty = json_encode($json,JSON_PRETTY_PRINT);
      file_put_contents($filename,$pretty);
      return $pretty;
    }
    public function config_set($name,$set) {
      $current = JsonConfig::config_get($name);
      $filename = JsonConfig::root()."/$name.json";
      $json = is_array($set) ? $set : json_decode($set,TRUE);
      if (!is_array($json)) die("Invalid JSON: $set\n");
      foreach($json as $k=>$v) { $current[$k] = $v; } 
      $pretty = json_encode($current,JSON_PRETTY_PRINT);
      file_put_contents($filename,$pretty);
      return $pretty;
    }
    public function config_read($name) {
      $filename = JsonConfig::root()."/$name.json";
      $json = @file_get_contents($filename) ?: '[]';
      $pretty = json_encode(json_decode($json,TRUE),JSON_PRETTY_PRINT);
      return $pretty;
    }
    public function config_get($name) {
      $filename = JsonConfig::root()."/$name.json";
      $json = @file_get_contents($filename) ?: '[]';
      $data = json_decode($json,TRUE);
      return $data;
    }
  
}