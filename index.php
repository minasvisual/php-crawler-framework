<?php
$config = include_once "./config.php";

require_once $config->ROOT_PATH . "vendor/autoload.php";

use Core\Engine;
use Core\Helpers;

$Engine = new Engine($config);
$Helpers = new Helpers($config);

$models = $Helpers->getModels();
$Helpers->inspect($models);
// Read $url and $sel from request ($_POST | $_GET)
$modelName = @$_POST['model'] ?: @$_GET['model'];
$url = @$_POST['url'] ?: @$_GET['url'];
$sel = @$_POST['sel'] ?: @$_GET['sel'];
$go  = @$_POST['go']  ?: @$_GET['go'];
$rm = strtoupper(getenv('REQUEST_METHOD') ?: $_SERVER['REQUEST_METHOD']);


            $Helpers->inspect("Request $go e $rm");
// var_export(compact('url', 'sel', 'go')+[$rm]+$_SERVER);
if ( $rm == 'POST' ) {
    // Results acumulator
    $return = array();

    // If we have $url to parse and $sel (selector) to fetch, we a good to go
    if($modelName && $go == 'json') {
        try {
            $Helpers->inspect("Entrou em URL" . $modelName);
            $return = $Engine->processModel($modelName);
          
            //$Helpers->inspect($return);
        }
        catch(Exception $ex) {
            $error = $ex;
        }
    } 
    else if( $go == 'cron') {
        try {
            $Helpers->inspect("Entrou em batch go " . $go);
            $return = $Engine->processModelsBatch();
          
            //$Helpers->inspect($return);
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
<body>
    <header class="selector">
        <form name="hquery" action="" method="post">
            <p><label>URL: <input type="text" name="url" value="<?=htmlspecialchars(@$url, ENT_QUOTES);?>" placeholder="ex. https://mariauzun.com/portfolio" autofocus class="form-control" /></label></p>
            <p><label>Selector: <input type="text" name="sel" value="<?=htmlspecialchars(@$sel, ENT_QUOTES);?>" placeholder="ex. 'a[href] &gt; img[src]:parent'" class="form-control" /></label></p>
            <p>
              <label>Model: 
                <select name="model" class="form-control">
                  <option value="" selected disabled>Selecione..</option>
                  <?php foreach( $models as $model): ?>
                  <option value="<?= str_replace(".php","",$model);?>" ><?=$model;?></option>
                  <?php endforeach; ?>
                </select>
              </label>
            </p>

            <p>
                <button type="submit" name="go" value="json" class="btn btn-success">Get by Model</button>
                <button type="submit" name="go" value="elements" class="btn btn-success">Fetch elements</button>
                <button type="submit" name="go" value="meta" class="btn btn-success">Fetch meta</button>
                <button type="submit" name="go" value="source" class="btn btn-success">Fetch source</button>
                <button type="submit" name="go" value="cron" class="btn btn-success">Run Cron</button>
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
      
            case 'elements': if(!empty($elements)):?>
                <hr />
                <table style="width: 100%">
                    <thead><tr>
                        <th>pos.</th>
                        <th>html</th>
                        <th>view</th>
                    </tr></thead>
                    <tbody>
            <?php foreach($elements as $pos => $el): ?>
                        <tr>
                            <td><i class="col-xs-1"><?=$pos;?></i></td>
                            <td><code style="word-break:break-word;"><?=htmlspecialchars($el->outerHtml(), ENT_QUOTES);?></code>&nbsp;</td>
                            <td><?=$el->outerHtml();?></td>
                        </tr>
            <?php endforeach;?>
                    </tbody>
                </table>
            <?php
            endif;
            break;

            case 'meta':?>
                <ul class="list-group">
                    <li class="list-group-item">
                        hQuery::$cache_path: <?php echo hQuery::$cache_path ?>
                    </li>
                    <li class="list-group-item">
                        Size: <span data-name="doc.size" class="badge"><?=empty($doc)?'':$doc->size;?></span>
                        <br />
                    </li>
                    <li class="list-group-item">Read Time: <span class="badge"><span data-name="doc.read_time"><?php echo $doc->read_time?></span> ms</span><br /></li>
                    <li class="list-group-item">Index Time: <span class="badge"><span data-name="doc.index_time"><?php echo $doc->index_time?></span> ms</span><br /></li>
                    <li class="list-group-item">
                        Charset: <span data-name="doc.charset" class="badge"><?=empty($doc)?'':$doc->charset;?></span>
                        <br />
                    </li>
                    <li class="list-group-item">
                        Base URL: <span data-name="doc.base_url" class="badge"><?=empty($doc)?'':$doc->base_url;?></span>
                        <br />
                    </li>
                    <li class="list-group-item">
                        Href: <span data-name="doc.href" class="badge"><?=empty($doc)?'':$doc->href;?></span>
                        <br />
                    </li>
                    <li class="list-group-item">
                        Title: <span data-name="doc.title" class="badge"><?=empty($meta['title'])?'':$meta['title'];?></span>
                        <br />
                    </li>
                    <li class="list-group-item">
                        Description: <span data-name="doc.description" class="badge"><?=empty($meta['description'])?'':$meta['description'];?></span>
                        <br />
                    </li>
                    <li class="list-group-item">
                        Keywords: <span data-name="doc.keywords" class="badge"><?=empty($meta['keywords'])?'':$meta['keywords'];?></span>
                        <br />
                    </li>
                    <li class="list-group-item">
                        HTTP Headers: <pre data-name="doc.headers"><?=empty($meta['headers'])?'':$meta['headers'];?></pre>
                    </li>
                </ul>
            <?php
            break;

            case 'source':?>
                <pre><?=empty($doc)?'':htmlspecialchars($doc->html(), ENT_QUOTES);?></pre>
            <?php
            break;
        } ?>
    </section>
</body>
</html>
