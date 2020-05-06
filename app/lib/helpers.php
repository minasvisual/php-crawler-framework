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
  
    function getElemValue($doc, $selector)
    {
      try{
          if( !$doc ) return false;
          if( $doc || !is_callable($doc->find) ) $doc = hQuery::fromHTML($doc->html());

          if( !isset($selector) && is_callable($doc->text) )
          {
            $content = trim($doc->text());
          }
          else if( isset($selector) && is_string($selector) )
          {
            $content = trim($doc->find($selector)->text());
          }
          else if ( is_array($selector) )
          {
            $how = isset($selector['how']) ? $selector['how'] : 'text';
            $content = '';
            $doc = $doc->find($selector['selector']);

            if( isset($selector['child']) && isset($doc[ $selector['child'] ]) )
              $doc = $doc[ $selector['child'] ];

            if( isset($selector['attr']) )
              $content = $doc->attr($selector['attr']);
            else
              $content = $doc->$how();

            if( isset($selector['convert']) && is_callable($selector['convert']) )
              $content = $selector['convert']($content);
          }
          else
          {
            $content = trim($doc->text());
          }

          if( isset($selector['trim']) ) $content = trim($content);

          return $content;
      }catch(Exception $e){
          throw $e;
      }
    }

    function getItemsValue($item, $selectors)
    {
       $content = [];
       if( !is_array($selectors) ) return false;
       foreach($selectors as $chave => $selector)
       {
         $content[$chave] = $this->getElemValue($item, $selector);
       }
       return $content;
    }

    function getSchemaValue($doc, $schema)
    {
       $return = [];
       if( !$schema || !is_array($schema) ) throw new Exception("getSchemaValue - Model is not array");

       foreach( $schema as $chave => $selector)
       {
          $content;
          if( is_string($selector) )
          {
            $content = $this->getElemValue($doc, $selector);
          }
          else if( is_array($selector) && !isset($selector['listItem']) )
          {
            $content = $this->getElemValue($doc, $selector);
          }
          else if ( is_array($selector) && isset($selector['listItem']) )
          {
             $content = [];
             if( is_string($selector['listItem']) && !isset($selector['data']) )
             {
               $items = $doc->find($selector['listItem']);
               foreach($items as $k => $v)
               {
                 $content[] = $this->getElemValue($v, null);
               }
             }
             else if( is_string($selector['listItem']) && isset($selector['data']) )
             {
               $items = $doc->find($selector['listItem']);
               foreach($items as $k => $v)
               {
                 $content[] = $this->getItemsValue($v, $selector['data']);
               }

             }else{
                $content = [];
             }

          }

          $return[$chave] = $content; 
        }

        return $return;
    }
  
    function callScraper($rowUrl, $response)
    {
      try{
          $model = $response['model'];
          $this->log($response, $model->name);
        
          $doc = $this->callHttpRequest($rowUrl, null, $model);

          $this->log("Initialized Task Url $model->name batch - URL $rowUrl", $model->name);
        
          if($doc) 
          {
              $return = $this->getSchemaValue($doc, $model->schema);
              
              if( isset($model->success) && is_callable($model->success) )
                    call_user_func_array($model->success, [
                      [ "data" => $return, "response" => $doc ]
                    ]);
          }
          else 
          {
              if( isset($model->error) && is_callable($model->error) )
                    call_user_func_array($model->error, [ hQuery::$last_http_result ]);
              //$return['request'] = hQuery::$last_http_result;

              $this->error("Request fail for $model->name batch");
          }
        
          $this->log("End Task $model->name batch", $model->name);
        
          return $return;
        
      }catch(Exception $ex) 
      {
          if( isset($model->error) && is_callable($model->error) )
              call_user_func_array($model->error, [ $ex ]);

          $this->error("Error when $model->name batch ");
        
          return $ex;
      }
    }

    function callTask($closure, $response)
    {
      try{
        $model = $response['model'];
        $this->log("Initialized Task Function $model->name batch", $model->name);
        if( isset($closure) && is_callable($closure) )
          return call_user_func_array($closure, [$response]);
        else
          throw "Task cannot be called by function";
      }catch(Exception $err){
         throw $err;
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

        $client = new \GuzzleHttp\Client($config);
        $res = $client->get($url);
        //$this->inspect($res->getHeader('content-type')[0]);
        //file_put_contents( __DIR__ . "/../logs/gzze.html", trim($res->getBody()->getContents()));
        //return hQuery::fromUrl( $url, $config['headers'] );
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