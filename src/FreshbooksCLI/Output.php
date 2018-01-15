<?php 
namespace FreshbooksCLI;
class Output {
  
  public function markdown_html($markdown) {

    // turn markdown into html
    $parsedown = new \Parsedown();
    $output = $parsedown->text($markdown);
    return $output;
    
  }
  public function formatted_json($data) {
    
    // turn array into pretty printed json
    $output = json_encode($data,JSON_PRETTY_PRINT);
    return $output;
    
  }
  public function formatted_yaml($data) {
    
    // turn array into pretty printed json
    $output = \FreshbooksCLI\YAML::encode($data);
    return $output;
    
  }
  public function border_box($content) {
    
    // if content is an array, implode keys and values into a content string
    if (is_array($content)) {
      foreach($content as $k=>$v) {
        if (is_array($v))
          $v = implode(', ',$v);
        if (!is_numeric($k))
          $content[$k] = "$k: $v";
      }
      $content = implode("\n",$content);
    }
    
    // remove white space from top and bottom of content
    $content = trim($content);
    
    // break content into lines
    $lines = explode("\n",$content);
    
    // find length of longest line
    $longest = 0;
    foreach($lines as $line) {
      if (mb_strlen($line) > $longest) 
        $longest = mb_strlen($line);
    }
    
    // print top dash line and top padding line
    $output = "+".str_repeat('-',$longest+4)."+\n";
    $output .= "|  ".str_repeat(' ',$longest)."  |\n";
    
    // print content lines
    foreach($lines as $line) {
      $output .= "|  ".$line.str_repeat(' ',$longest-mb_strlen($line))."  |\n";
    }
    
    // print bottom dash line and top padding line
    $output .= "|  ".str_repeat(' ',$longest)."  |\n";
    $output .= "+".str_repeat('-',$longest+4)."+\n";
    
    // return output string 
    return $output;
  }
  public function whitespace_table($rows) {

    // check if this is a single column of values
    $is_one_column = !@is_array(current($rows)) && @is_numeric(key($rows));
    
    // if this is one colomn, turn each value into a row
    if ($is_one_column) { 
      $values = $rows; 
      foreach($values as $i=>$value) {
        $rows[$i] = array($value);
      }
    }
    
    // check if rows uses numeric keys
    $uses_nonnumeric_keys = !empty($rows) && !@is_numeric(key(current($rows)));
    
    // build and add header row if nonnumeric keys
    if ($uses_nonnumeric_keys) { 
      $header_row = array();
      $divider_row = array();
      $keys = @array_keys(current($rows));
      if (is_array($keys)) foreach($keys as $header) {
        $header_row[] = strtoupper($header);
        $divider_row[] = str_repeat('-',mb_strlen($header));
      }
      array_unshift($rows,$divider_row);
      array_unshift($rows,$header_row);
    }
    
    // determine largest size for each column
    $column_sizes = array();
    if (is_array($rows)) foreach($rows as $cols) { 
      $cols = @array_values($cols);
      if (is_array($cols)) foreach($cols as $i=>$value) {
        if (is_array($value)) $value = implode(', ',$value);
        if (empty($column_sizes[$i]) || mb_strlen($value) > $column_sizes[$i]) {
          $column_sizes[$i] = mb_strlen($value);
        }
      }
    }
    
    // create lines from rows of column values
    $lines = array();
    if (is_array($rows)) foreach($rows as $cols) {
      $cols = @array_values($cols);
      $line = array();
      if (is_array($cols)) foreach($cols as $i=>$value) {
        if (is_array($value)) $value = implode(', ',$value);
        $line[] = str_pad($value,$column_sizes[$i]," ");
      }
      $lines[] = trim(implode("   ",$line));
    }
    
    // implode into output and return
    $output = implode("\n",$lines)."\n";
    return $output;
    
  }
  
  
}