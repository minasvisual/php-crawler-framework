<?php
//
// DOCS http://docs.guzzlephp.org/en/stable/
//
namespace Core;

use \GuzzleHttp\Client;
use \Exception;
use \Core\Helpers;

class Database {
    public $config;
    public $client;
    public $con;
  
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

      $this->client = new \TORM\Model();
    }
  
    public function connect($dbKey){
      $db = $this->config->db[$dbKey];
      if( !isset($db) ) throw new Exception("Connection dont exists");
      
      try{
        $this->con = new \PDO(sprintf("%s:host=%s;port=%s;dbname=%s", $db['driver'], $db['host'],$db['port'], $db['database']), $db['username'], $db['password']);
        \TORM\Connection::setConnection($this->con);
        \TORM\Connection::setDriver($db['driver']);
      }catch(Exception $err){
        throw $err;
      }
      $this->con;
    }
  
//     public function newModel($name){
      
//             (new ClassEventsDev()) extends \TORM\Model { }
            
//             EventsDev::setTableName('events');
//             EventsDev::setPK('id');
//             EventsDev::setIgnoreCase(true);
          
//             return  EventsDev::find(145);
          
//     }
}