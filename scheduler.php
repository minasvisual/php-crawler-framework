<?php 

require_once __DIR__.'/vendor/autoload.php';
$config = include_once __DIR__ . "/config.php";
  
use GO\Scheduler;
use Core\Engine;
use Core\Helpers;

$Engine = new Engine($config);
$Helpers = new Helpers($config);

$transport = (new Swift_SmtpTransport($config->smtp['host'], $config->smtp['port'], $config->smtp['secure']))
    ->setUsername($config->smtp['username'])
    ->setPassword($config->smtp['password']);

// Create a new scheduler
$scheduler = new Scheduler([
    'email' => [
        'subject' => 'MinasVisual',
        'from' => $config->smtp['from'],
        'body' => 'This is the daily visitors count',
        'transport' => $transport,
        'ignore_empty_output' => false,
    ]
]);

// ... configure the scheduled jobs (see below) ..
$scheduler->call(function () use ($Helpers){
    $Helpers->log("Cron executado as ".date(c), 'cron');
    return true;
})->everyMinute(5)->email(['mantovaniarts@hotmail.com']);

// Let the scheduler execute jobs which are due.
$scheduler->run();