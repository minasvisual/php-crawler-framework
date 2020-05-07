<?php
//
// DOCS http://docs.guzzlephp.org/en/stable/
//
namespace Core;

use \GuzzleHttp\Client;
use \Exception;

class Request {
    public $config;
    public $client;
  
    public $defaultConfig = [
          "headers" => [
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.100 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Upgrade-Insecure-Requests' => 1,
          ]
    ];
  
    public function __construct($config=null)
    {
      $this->config = isset($config) ? $config : (include_once "../../config.php");

      $this->client = new \GuzzleHttp\Client($this->defaultConfig);
    }
  
    public function request($method, $url, $options = []){
      try{
        $res = $this->client->request($method, $url, $options);
        
        return $res;
      }catch(Exception $err){
        throw $err;
      }
    }
  
  
}