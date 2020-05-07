<?php
use \Core\Helpers;

$helpers = new Helpers(null);

$helpers->setConfig('debug', true);
$helpers->setConfig('logging', true);

return (object) [
  "name" => "example",
  "status" => true,
  "debug" => true,
  "log" => true,
  "schedule" => '2 * * * *',
  "header" => [],
  "tasks" => [
      ["url" => "https://ionicabizau.net"],
      ["task" => function($response) use ($helpers){
        $helpers->inspect("Called task all ".$response['model']->name ); 
        return 'OK';
      } ],
  ],
  "schema" => [
      "title" => ".header h1",
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
  "beforeAll" => function($model) use ($helpers){
    $helpers->inspect("Called before all");
    return $model;
  },
  "success" => function($response) use ($helpers){
     $helpers->inspect($response['data']);
  },
  "error" => function($err) use ($helpers){
    $helpers->inspect($response);
  },
  "afterAll" => function($data) use ($helpers){
    $helpers->inspect("Called after all");
    
    return $data;
  }
  // export
];
