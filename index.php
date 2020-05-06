<?php
$config = include_once "./config.php";

require_once $config->ROOT_PATH . "vendor/autoload.php";

use Core\Engine;
use Core\Helpers;

$Engine = new Engine($config);
$Helpers = new Helpers($config);

$models = $Helpers->getModels();
// Read $url and $sel from request ($_POST | $_GET)
$modelName = @$_POST['model'] ?: @$_GET['model'];
$url = @$_POST['url'] ?: @$_GET['url'];
$sel = @$_POST['sel'] ?: @$_GET['sel'];
$go  = @$_POST['go']  ?: @$_GET['go'];
$rm = strtoupper(getenv('REQUEST_METHOD') ?: $_SERVER['REQUEST_METHOD']);

// var_export(compact('url', 'sel', 'go')+[$rm]+$_SERVER);
if ( $rm == 'POST' ) {
    // Results acumulator
    $return = array();

    // If we have $url to parse and $sel (selector) to fetch, we a good to go
    if($modelName && $go == 'json') {
        try {
            $return = $Engine->processModel($modelName);
        }
        catch(Exception $ex) {
            $error = $ex;
        }
    } 
    else if( $go == 'cron') {
        try {
            $return = $Engine->processModelsBatch();
        }
        catch(Exception $ex) {
            $error = $ex;
        }
    }  
    else if( isset($url) && isset($sel) && $go == 'url') {
        try {
            $doc = $Helpers->callHttpRequest($url, null, (object)["name"=>"Standalone"]);
            $return = $Helpers->getElemValue($doc, $sel);
        }
        catch(Exception $ex) {
            $error = $ex;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf8" />
    <title>hQuery playground example</title>
    <style lang="css">
        * {
            box-sizing: border-box;
        }
        html, body {
            position: relative;
            min-height: 100%;
        }
        header, section {
            margin: 10px auto;
            padding: 10px;
            width: 90%;
            max-width: 1200px;
            border: 1px solid #eaeaea;
        }

        input {
            width: 100%;
        }
    </style>
</head>
<body ng-app="app" ng-controller="MainCtrl">
    <header class="selector">
        <form name="hquery" action="" method="post">
           <p>
              <label>Choose way to RUN: 
                <select name="go" class="form-control"  ng-model="form.go" > 
                  <option value="" selected disabled>Selecione..</option>
                  <option value="url" >URL</option>
                  <option value="json" >MODEL</option>
                  <option value="cron" >Run Batch Models</option>
                </select>
              </label>
            </p>
            <p><label>URL:      <input ng-model="form.url" ng-disabled="form.go != 'url'" type="text" name="url" value="<?=htmlspecialchars(@$url, ENT_QUOTES);?>" placeholder="ex. https://mariauzun.com/portfolio" autofocus class="form-control" /></label></p>
            <p><label>Selector: <input ng-model="form.sel" ng-disabled="form.go != 'url'"   type="text" name="sel" value="<?=htmlspecialchars(@$sel, ENT_QUOTES);?>" placeholder="ex. 'a[href] &gt; img[src]:parent'" class="form-control" /></label></p>
            <p>
              <label>Model: 
                <select name="model" class="form-control"  ng-model="form.model" ng-disabled="form.go != 'json'" > 
                  <option value="" selected disabled>Selecione..</option>
                  <?php foreach( $models as $model): ?>
                  <option value="<?= str_replace(".php","",$model);?>" ><?=$model;?></option>
                  <?php endforeach; ?>
                </select>
              </label>
            </p>

            <p>
                <button type="submit" name="go" value="url" class="btn btn-success">RUN</button>
            </p>

            <?php if( !empty($error) ): ?>
            <div class="error">
                <h3>Error:</h3>
                <p>
                    <?=$error->getMessage();?>
                </p>
            </div>
            <?php endif; ?>
        </form>
    </header>

    <section class="result">
        <?php switch ($go) {
            case 'cron': 
                 echo '<pre style="word-break:break-word;">'.json_encode($return, JSON_PRETTY_PRINT)."</pre>"; 
            break; 
  
            case 'json': 
                 echo '<pre style="word-break:break-word;">'.json_encode($return, JSON_PRETTY_PRINT)."</pre>"; 
            break;
            default:
                echo '<pre style="word-break:break-word;">'.json_encode($return, JSON_PRETTY_PRINT)."</pre>"; 
            break;
        } ?>
    </section>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/angular.js/1.7.9/angular.min.js"></script>
  <script>
      angular.module('app', [])
        .controller('MainCtrl', ($scope) => {
            $scope.form = {};
        })
  </script>
</body>
</html>
