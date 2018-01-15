<?php 
namespace FreshbooksCLI;
class YAML {

  function decode($yaml_string) {
    $yaml_array = \Spyc::YAMLLoadString($yaml_string);
    return $yaml_array; 
  }
  function encode($yaml_array) {
    $yaml_string = \Spyc::YAMLDump($yaml_array);
    return $yaml_string;
  }

}