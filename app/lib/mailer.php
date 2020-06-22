<?php
//
// DOCS https://swiftmailer.symfony.com/docs
//
namespace Core;

use \Exception;

class Mailer {
    public $config;
    public $transport;
    public $mailer;
  
    public function __construct($config=null)
    {
      $this->config = isset($config) ? $config : (include __DIR__ . "/../../config.php");

      $this->transport = (new \Swift_SmtpTransport(
              $this->config->smtp['host'], 
              $this->config->smtp['port'], 
              ( isset($this->config->smtp['secure']) ? $this->config->smtp['secure'] : '' )
          ))
          ->setUsername($this->config->smtp['username'])
          ->setPassword($this->config->smtp['password']);
      
      // Create the Mailer using your created Transport
      $this->mailer = new \Swift_Mailer($this->transport);
    }
  
    public function newMessage($subject = ''){
      return (new \Swift_Message($subject))->setFrom([$this->config->smtp['from']]);
    }
  
    public function send($message){
      return $this->mailer->send($message);
    }
  
}