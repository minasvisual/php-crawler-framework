<?php

return (object) [
  "MODEL_PATH" => __DIR__ . '/app/models/',
  "APP_PATH" => __DIR__ . '/app/',
  "ROOT_PATH" => __DIR__ . '/',
  
  "debug" => true,
  "log" => true,
  'db' => [
      'driver' => '',
      'host' => '',
      'database' => '',
      'username' => '',
      'password' => '',
      'charset'   => '',
      'collation' => '',
      'prefix'    => ''
  ],
  
  "smtp" => [
      'host' => '',
      'port' => 0,
      'username'   => '',
      'password' => '',
      'secure' => '',
      'from'    => '',
  ]
];