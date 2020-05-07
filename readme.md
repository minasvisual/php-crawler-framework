# UNDER CONSTRUCTION

## Installation

1. Clone this repository

2. enter folder: cd php-crawler-framework

3. Install dependencies: composer install

3. rename Config.example.php to config.php

## Usage

Copy app/examples/example.php to app/models/ (if folder not exists, create it!)

Open playground (http://localhost/path/to/application/) and choose example model and click in "Get by model"

## Model body
```
<?php
use \Core\Helpers;

$helpers = new Helpers(null); // Helpers Instance

return (object) [
  "name" => "example",  // Model Name
  "status" => true,     // Model Runnable Status | If False, will not be runned
  "debug" => true,      // Run Debug (Enable inspection on console log) 
  "log" => true,        // File Logs
  "schedule" => '*/2 * * * *',  // Schedule cron enable | set Null to disable cron
  "header" => [],    // Http Request special headers
  "tasks" => [   // Tasks, in sort of execution
      ["url" => "https://ionicabizau.net"],                  // Task type URL is a scraper 
      ["task" => function($response) use ($helpers){         // Task type Task is a callback runnable function
        $helpers->inspect("Called task all ".$response['model']->name ); 
        return 'OK';
      } ],
  ],
  "schema" => [                    // Schema is a json structure to return on each seletors 
      "title" => ".header h1",     // See scraper docs on in https://github.com/IonicaBizau/scrape-it
      "desc" => [
        "selector"=>".header h2",
        "convert" => function($x){ return trim($x); }
      ],
      "avatar" => [
        "selector" => ".header img",
        "attr" => "src"
      ],
      "pages" => [
          'listItem' => ".pages .page a"
      ],
      "active" => [
          'selector' => ".pages .page a",
          'child' => 2, // starts by 0
          'trim' => true,
          'attr' => 'href'
      ],
      "nav" => [
            'listItem' => ".pages .page",
            'data'=> [
                'title'=> "a",
                'url'=> [
                    'selector'=> "a",
                    'attr'=> "href"
                ]
            ]
        ],
      "header" => [
          "selector" => ".header h1",
          "how" => "html"
      ],
  ],
  "beforeAll" => function($model) use ($helpers){  // Called before start tasks | REQUIRED return of $model (updated or not)
    $helpers->inspect("Called before all");
    return $model;
  },
  "success" => function($response) use ($helpers){ // Called on each success callback tasks
     $helpers->inspect($response['data']);
  },
  "error" => function($err) use ($helpers){       // Called on each error callback tasks
    $helpers->inspect($response);
  },
  "afterAll" => function($data) use ($helpers){   // Called after all tasks done | REQUIRED return $data (updated or not)
    $helpers->inspect("Called after all");
    
    return $data;
  }
  // export
];
```

## Thirty part libs

- https://github.com/FriendsOfPHP/Goutte
- https://github.com/duzun/hQuery.php
- https://github.com/IonicaBizau/scrape-it
- https://github.com/peppeocchi/php-cron-scheduler
- https://github.com/swiftmailer/swiftmailer
- https://github.com/guzzle/guzzle.git
- https://github.com/taq/torm/

## Contribute


## Issues
- [x]  Playground UI
- [x]  Manual batch UI
- [x]  Schedule integrated in model
- [ ]  Database multi connection
- [ ]  Proxy ramdomic funciton
- [ ]  UI manager and monitoring (read logs, console ui, charts)
- [ ]  Tasks sub schemas 
- [ ]  Model Builder
- [ ]  EMkt Builder