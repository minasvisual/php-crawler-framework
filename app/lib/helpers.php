<?php

namespace Core;

use \GuzzleHttp\Client;
use duzun\hQuery;
use \Exception;

class Helpers { 
  
    public $debug = true;
    public $logging = true;
    public $config;
  
    public function __construct($config)
    {
      $this->config =  $config ?: (include_once __DIR__ . '/../../config.php');
    }
    
    public function setConfig($name, $value)
    {
        $this->$name = $value;
    }

    public function getModels($folder = null)
    {
      $folder =  isset($folder) ? $folder : $this->config->MODEL_PATH ;
      if( isset($folder) && is_dir( $folder ) ){
        return array_filter(
            scandir( $folder ), 
            function($row){ return strpos($row, '.php') !== FALSE; } 
        );
      }else{
        return false;
      }
    }

    public function getModel($name)
    {
      if( isset($name) && file_exists( $this->config->MODEL_PATH . $name .'.php' ) ){
        //ob_start();
        return include $this->config->MODEL_PATH . $name. '.php';
        //return ob_get_clean();
      }else{
        return false;
      }
    }
  
  
    function callHttpRequest($url, $config, $model)
    {
      try{
        $defaultConfig = [
          "headers" => [
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.100 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Upgrade-Insecure-Requests' => 1,
          ]
        ];
        // Enable cache
        $cacheDir =  '/hQuery/';
        $config = isset($config) ? array_merge($defaultConfig, $config): $defaultConfig;
        
        hQuery::$cache_path = sys_get_temp_dir() . $cacheDir;

        $client = new \Core\Request($config);
        
        if( isset($config['method']) ){
          $method = $config['method'];
          $res = $client->request($config['method'], $url);
        }else{
          $res = $client->request('GET', $url);
        }
        
        if( $res->getStatusCode() < 200 || $res->getStatusCode() > 399 ) 
            throw new Exception("Request failed with status ". $res->getStatusCode(), $res->getBody()->getContents());
          
        return hQuery::fromHTML( trim($res->getBody()->getContents()), $url);
        //return hQuery::fromUrl( $url, $config['headers'] );
      }catch(Exception $err){
        $this->error(["message"=> "Request error $model->name ", "url"=>$url, "model"=>$model]);
        $this->error($err);
        throw $err;
      }
    }
  
    public function inspect($data)
    {
       if( $this->debug !== true ) return false;
         
       echo "<script>console.log('".json_encode($data)."');</script>";
       return json_encode($data);
    }
 
    public function log($data, $filename = 'system.log', $eng = FILE_APPEND )
    {
       if( $this->logging !== true ) return false;
        
       $log = json_encode(["timestamp" => date('c'), "level" => "log", "data" => $data]) ."\r\n";
       return file_put_contents( $this->config->APP_PATH .'logs/'. ( isset($filename) ? $filename : 'system.log'), $log, $eng );
    }
  
    public function error($data, $filename = 'system.log', $eng = FILE_APPEND )
    {
       if( $this->logging !== true ) return false;
      
       $log = json_encode(["timestamp" => date('c'), "level" => "error", "data" => $data ]) ."\r\n";
       return file_put_contents( $this->config->APP_PATH .'logs/'. ( isset($filename) ? $filename : 'system.log'), $log, $eng );
    }

}