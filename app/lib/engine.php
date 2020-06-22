<?php

namespace Core;

use \Core\Helpers;
use duzun\hQuery;
use \Exception;

class Engine{
  
    public $config;
    public $Helpers;
  
    public function __construct($config)
    {
      $this->config = $config;
      $this->Helpers = new Helpers($this->config);
    }
  
    function processModel($modelName)
    {      
      
        if ( !isset($modelName) || !file_exists( $this->config->MODEL_PATH . $modelName.'.php') )
        {
          if( !isset($modelName) )
              $msg = "Params modelName not not exists";
            else if( file_exists('./models/'.$modelName.'.php') )
              $msg = "$modelName file not found";
            else
              $msg = "unknown error".$modelName;
          
            $this->Helpers->error($msg);
          
            throw new Exception($msg);
        }
      
        $model = $this->Helpers->getModel($modelName);

        $this->Helpers->setConfig('debug', $model->debug);
        $this->Helpers->setConfig('logging', $model->log);
        // If we have $url to parse and $sel (selector) to fetch, we a good to go
        if( !isset($modelName) || !isset($model) || !isset($model->status) ) 
        {
          $this->Helpers->error("$modelName batch or model error");
          $this->Helpers->error($model, $model->name);

          throw new Exception( "$modelName batch or model error" );
        }
      
        if( $model->status !== true ){
           $this->Helpers->log("$modelName batch inactive, change config status to run");
           return false;
        }
          
        // Results acumulator
        $return = array("response"=>[]);

        $this->Helpers->log("Initialized $model->name batch");

        if( isset($model->beforeAll) && is_callable($model->beforeAll) )
            $model = call_user_func_array($model->beforeAll, [$model]);

        if( !isset($model) || is_null($model) || empty($model) )
            throw "Is required beforeAll return a model instance received"; 

        if( !is_array($model->tasks) ) $model->tasks = [ $model->tasks ];

        foreach( $model->tasks as $k => $task )
        {
            try {
                $select_time = microtime(true);

                $response = ["model" => $model, $data => $return['response'] ];

                if( isset($task['url']) && !empty($task['url']) && is_string($task['url']) ) 
                  $return['response'][$k] =  $this->callScraper($task['url'], $response);

                if( isset($task['url']) && !empty($task['url']) && is_array($task['url']) ) 
                {
                    foreach( $task['url'] as $vUrl){
                      $return['response'][$k][] = $this->callScraper($vUrl, $response);
                    }
                }

                else if( isset($task['task']) && is_callable($task['task']) ) 
                  $return['response'][$k] = $this->callTask($task['task'], $response);

                $select_time = microtime(true) - $select_time;

                $return['select_time'] = $select_time;

            }
            catch(Exception $ex) {
              
                if( isset($model->error) && is_callable($model->error) )
                    call_user_func_array($model->error, [ $ex ]);

                $this->Helpers->error("Error when $model->name batch ");

                return $ex;
            }

        }


        if( isset($model->afterAll) && is_callable($model->afterAll) )
           $return = call_user_func_array($model->afterAll, [$return]);

        $this->Helpers->log("End $model->name batch");

        return $return;
    }
  
    function processModelsBatch($folder=null)
    {
        $Helpers = new Helpers($this->config);
        
        $models = array_map(function($i){ return str_replace('.php', '', $i); }, $this->Helpers->getModels($folder));
        $this->Helpers->inspect($models);
      try{
        $this->Helpers->inspect("Cron Batch initalized");
        foreach($models as $modelName){
            $this->Helpers->inspect($modelName);
            $this->processModel($modelName);
            sleep(1);
        }
        $this->Helpers->inspect("Cron Batch Ended");
          return true;
      }catch(Exception $err){
          return $err;
      }
        
    }
  
    function getElemValue($doc, $selector)
    {
      try{
          if( !$doc || !isset($doc) ) return false;
          if( !empty($doc) || empty($doc->find($selector)) ) $doc = hQuery::fromHTML($doc->html());

          if( !isset($selector) && is_callable($doc->text) )
          {
            $content = trim($doc->text());
          }
          else if( isset($selector) && is_string($selector) && !empty($doc->find($selector)) )
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
          else if( isset($doc) && !empty($doc->text()) )
          {
            $content = trim($doc->text());
          }
          else
          {
            $content = "";
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
          $this->Helpers->log($response, $model->name);
        
          [$doc, $res] = $this->Helpers->callHttpRequest($rowUrl, null, $model);

          $this->Helpers->log("Initialized Task Url $model->name batch - URL $rowUrl", $model->name);
        
          if($doc) 
          {
              $return = $this->getSchemaValue($doc, $model->schema);
              
              if( isset($model->success) && is_callable($model->success) )
                    call_user_func_array($model->success, [
                      [ "url" => $rowUrl, "data" => $return, "doc" => $doc , "response" => $res]
                    ]);
          }
          else 
          {
              if( isset($model->error) && is_callable($model->error) )
                    call_user_func_array($model->error, [ hQuery::$last_http_result ]);
              //$return['request'] = hQuery::$last_http_result;

              $this->error("Request fail for $model->name batch");
          }
        
          $this->Helpers->log("End Task $model->name batch", $model->name);
        
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
        $this->Helpers->log("Initialized Task Function $model->name batch", $model->name);
        if( isset($closure) && is_callable($closure) )
          return call_user_func_array($closure, [$response]);
        else
          throw "Task cannot be called by function";
      }catch(Exception $err){
         throw $err;
      }
    }
}