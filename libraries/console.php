<?php
class Console {
  
  public $enabled = true;
  protected $index = 1;
  public $log_path = '';
  public $log_file = '';
  private $CI;

  function Console($enable=true) {
    $this->CI =& get_instance();
    $config =& get_config();
    $this->log_path = ($config['log_path'] != '') ? $config['log_path'] : APPPATH.'logs/';
    $this->log_file = 'console-'.date('Y-m-d').'.php';
    $this->enabled = $enable;
  }
  
  /**
   * Log data to the fireBug Console (via firePHP)
   * @param Mixed $type
   * @param Mixed $message
   * @param Bool $write_to_file [optional]
   */
  public function log($message, $type='log', $write_to_file=false) {
    $header_name = 'X-Wf-1-1-1-'.$this->index;
    
    if (!is_array($type) && !is_object($type)) {
      if (in_array(strtolower($type), array('log','info','warn','error'))) {
        // create header value
        $header_value = '[{"Type":"'.strtoupper($type).'"},'.json_encode($message).']';
      }
      else {
        // fallback if $type was incorrect
        $this->log('FirePHP: The log type: '.$type.' is Invalid', 'error', true);
        $header_value = '[{"Type":"LOG"},'.json_encode($message).']';
      }
      // write to log file
      if ($write_to_file==true) {
        $this->write($message, $type);
      }
    }
    else {
      $meta;
      // create meta Object
      foreach ($type as $key=>$value) {
        $key = ucfirst($key);
        $meta->$key = $value;
      }

      $body;
      // create body object
      foreach ($message as $key=>$value) {
        $key = ucfirst($key);
        $body->$key = $value;
      }
      // create header value
      $header_value = '['.json_encode($meta).','.json_encode($body).']';
      
      if ($write_to_file==true) {
        $this->write($meta->Type, $body->Trace.': '.json_decode($body->Trace));
      }
    }
    
    if ($this->enabled) {
      if ($this->index==1) {
        // set base firePHP headers
        $this->CI->output->set_header('X-Wf-Protocol-1: http://meta.wildfirehq.org/Protocol/JsonStream/0.2');
        $this->CI->output->set_header('X-Wf-1-Plugin-1: http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.3');
        $this->CI->output->set_header('X-Wf-1-Structure-1: http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1');
      }
      
      // set output header
      $this->CI->output->set_header($header_name.': '.strlen($header_value).'|'.$header_value.'|');
      
      // increase log index
      $this->index++;
    }
  }
  
  /**
   * Write Log to a file
   * 
   * @param Mixed $message
   * @param String $type
   * @return Bool
   */
  public function write($message, $type='LOG') {
    $log = '';
    if ( ! file_exists($this->log_path.$this->log_file)) {
      $log .= "<"."?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?".">\n\n";
    }
    $log .= strtoupper($type).' - '.date('Y-m-d h:i:s').' --> '.print_r($message, true)."\n";
    if (@file_put_contents($this->log_path.$this->log_file, $log, FILE_APPEND)) {
      return true;
    }
    else {
      log_message('error', 'Failed to write console log file');
    }
  }
}

if (!function_exists('console_log')) {
  function console_log($message, $type="LOG", $write_to_file=false) {
    $CI =& get_instance();
    $CI->console->log($message, $type, $write_to_file);
  }
}
if (!function_exists('console_write')) {
  function console_write($message, $type="LOG") {
    $CI =& get_instance();
    $CI->console->write($message, $type);
  }
}

/* End of file console.php */
/* Location: sparks/console/.../libraries/console.php */