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
  if( isset($model) && isset($model->schedule) )
  {
    $Helpers->inspect("Initialized CRON Schedule of ".$model->schedule);
    $scheduler->call(function() use ($Engine, $Helpers, $model, $modelName) {
        $Helpers->log("------------------------------ \r\n Initialized CRON Schedule of ".$modelName);
        $Engine->processModel($modelName);
        return true;
    })->at($model->schedule)->output( __DIR__.'/app/logs/cron.txt');
  }
}

$scheduler->call(function() use ($Engine, $Helpers) {
        $Helpers->log(" -------------- Cron called -------------", 'cron.txt');
        return true;
})->at('* * * * *');
// Let the scheduler execute jobs which are due.
$scheduler->run();