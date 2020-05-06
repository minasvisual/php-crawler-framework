<?php

namespace Core;

use \Core\Helpers;
use \Exception;

class Engine{
  
    public $config;
  
    public function __construct($config)
    {
      $this->config = $config;
    }
  
    function processModel($modelName)
    {      
        $Helpers = new Helpers($this->config);
      
        if ( !isset($modelName) || !file_exists( $this->config->MODEL_PATH . $modelName.'.php') )
        {
          if( !isset($modelName) )
              $msg = "Params modelName not not exists";
            else if( file_exists('./models/'.$modelName.'.php') )
              $msg = "$modelName file not found";
            else
              $msg = "unknown error".$modelName;
          
            $Helpers->error($msg);
          
            throw new Exception($msg);
        }
      
        $model = $Helpers->getModel($modelName);

        $Helpers->setConfig('debug', $model->debug);
        $Helpers->setConfig('logging', $model->log);
        // If we have $url to parse and $sel (selector) to fetch, we a good to go
        if( !isset($modelName) || !isset($model) || !isset($model->status) ) 
        {
          $Helpers->error("$modelName batch or model error");
          $Helpers->error($model, $model->name);

          throw new Exception( "$modelName batch or model error" );
        }
      
        if( $model->status !== true ){
           $Helpers->log("$modelName batch inactive, change config status to run");
           return false;
        }
          
        // Results acumulator
        $return = array("response"=>[]);

        $Helpers->log("Initialized $model->name batch");

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
                  $return['response'][$k] =  $Helpers->callScraper($task['url'], $response);

                if( isset($task['url']) && !empty($task['url']) && is_array($task['url']) ) 
                {
                    foreach( $task['url'] as $vUrl){
                      $return['response'][$k][] = $Helpers->callScraper($vUrl, $response);
                    }
                }

                else if( isset($task['task']) && is_callable($task['task']) ) 
                  $return['response'][$k] = $Helpers->callTask($task['task'], $response);

                $select_time = microtime(true) - $select_time;

                $return['select_time'] = $select_time;

            }
            catch(Exception $ex) {
              
                if( isset($model->error) && is_callable($model->error) )
                    call_user_func_array($model->error, [ $ex ]);

                $Helpers->error("Error when $model->name batch ");

                return $ex;
            }

        }


        if( isset($model->afterAll) && is_callable($model->afterAll) )
           $return = call_user_func_array($model->afterAll, [$return]);

        $Helpers->log("End $model->name batch");

        return $return;
    }
  
    function processModelsBatch($folder=null)
    {
        $Helpers = new Helpers($this->config);
        
        $models = array_map(function($i){ return str_replace('.php', '', $i); }, $Helpers->getModels($folder));
        $Helpers->inspect($models);
      try{
        $Helpers->inspect("Cron Batch initalized");
        foreach($models as $modelName){
            $Helpers->inspect($modelName);
            $this->processModel($modelName);
            sleep(1);
        }
        $Helpers->inspect("Cron Batch Ended");
          return true;
      }catch(Exception $err){
          return $err;
      }
        
    }
}