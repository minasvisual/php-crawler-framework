<?php 

require_once __DIR__.'/vendor/autoload.php';
$config = include_once __DIR__ . "/config.php";
  
use GO\Scheduler;
use Core\Engine;
use Core\Helpers;
use Core\Mailer;

$Engine = new Engine($config);
$Helpers = new Helpers($config);
$Mailer = new Mailer($config);

$models = array_map(function($i){ return str_replace('.php', '', $i); }, $Helpers->getModels());

// Create a new scheduler
$scheduler = new Scheduler([
    'email' => [
        'subject' => 'System report',
        'from' => $config->smtp['from'],
        'body' => 'System report cron',
        'transport' => $Mailer->transport,
        'ignore_empty_output' => false,
    ]
]);
foreach( $models as $modelName )
{
  $model = $Helpers->getModel($modelName);
  if( $model && isset($model->schedule) )
  {
    $scheduler->call(function() use ($Engine, $Helpers, $model, $modelName) {
        $Helpers->log("Initialized CRON Schedule of ".$modelName);
        $Engine->processModel($modelName);
    })->at($model->schedule);
  }
}
  
$scheduler->call(function () use ($Helpers) {
    $Helpers->log("Cron executado as ".date('c'), 'cron');
    return "Cron executado as ".date('c');
})->everyMinute(5)->output('./app/logs/cron.txt')->email([$config->smtp['to']]);

// Let the scheduler execute jobs which are due.
$scheduler->run();