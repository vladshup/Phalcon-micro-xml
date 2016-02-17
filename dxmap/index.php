<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
session_start();
include_once (dirname(__FILE__) . '/lib/loaderPHPClass.php');
$client = new Elasticsearch\Client();
$uri = htmlspecialchars($_SERVER['REQUEST_URI']);

$topdx = '';
$debug = '';

//Ask a most spoted dx
$searchParams['index'] = 'dxspider';
$searchParams['type']  = 'spots';
$searchParams['size']  = 0;
$searchParams['body']['query']['filtered']['query']['match_all'] = array();
$searchParams['body']['query']['filtered']['filter']['range']['_timestamp']['gte'] = time()*1000 - 60*60*1000;
$searchParams['body']['aggs']['topdx']['terms']['field'] = 'dx';
$searchParams['body']['aggs']['topdx']['terms']['size'] = 18;
$retDoc = $client->search($searchParams);

foreach($retDoc["aggregations"]["topdx"]["buckets"] as $agg){
$topdx .= "<tr><td><small><a href=\"/?q={$agg["key"]}\">" . $agg["key"] . "</a></small></td><td><small>". $agg["doc_count"] . "</small></td></tr>";
}

//Ask a band activity for last hour
$band_data = array();
$band = array("vlf","160m","80m","60m","40m","30m","20m","17m","15m","12m","10m","6m","4m","2m","70cm","23cm","6cm","3cm");

foreach ($band as $key => $value){
$searchParams1['index'] = 'dxspider';
$searchParams1['type']  = 'spots';
$searchParams1['body']['filter']['term']['band'] = $value;
$searchParams1['body']['query']['range']['_timestamp']['gt'] = 'now-1h';
$retDoc = $client->search($searchParams1);
$band_data["$key"] = $retDoc["hits"]["total"];
}
//var_dump(json_encode($data));

$debug = $uri;


$rnd = new renderPHPClass();
$rnd->o['debug'] = time() - filectime (dirname(__FILE__) . "/assets/img/test.txt");
$rnd->o['data'] = json_encode($band_data);
$rnd->o['topdx'] = $topdx;
$rnd->o['year'] = date('Y');

$url = $_SERVER['REQUEST_URI'];

$rnd->o['/'] = $rnd->o['vlf'] = $rnd->o['160m'] = $rnd->o['80m'] = $rnd->o['60m'] = '' ;
$rnd->o['40m'] = $rnd->o['30m'] = $rnd->o['20m'] = $rnd->o['17m'] = $rnd->o['15m'] = '' ;
$rnd->o['12m'] = $rnd->o['10m'] = $rnd->o['6m'] = $rnd->o['4m'] = $rnd->o['2m'] = '' ;
$rnd->o['70cm'] = $rnd->o['23cm'] = $rnd->o['6cm'] = $rnd->o['3cm'] = '' ;


switch($url){
case "/": $rnd->o['/'] = " h-menu-act"; $rnd->o['title'] = "DX cluster spots"; break;
case "/vlf": $rnd->o['vlf'] = " h-menu-act"; $rnd->o['title'] = "VLF 2200m and 600m dx-cluster spots"; break;
case "/160m": $rnd->o['160m'] = " h-menu-act"; $rnd->o['title'] = "160m dx-cluster spots"; break;
case "/80m": $rnd->o['80m'] = " h-menu-act"; $rnd->o['title'] = "80m dx-cluster spots"; break;
case "/60m": $rnd->o['60m'] = " h-menu-act"; $rnd->o['title'] = "60m dx-cluster spots"; break;
case "/40m": $rnd->o['40m'] = " h-menu-act"; $rnd->o['title'] = "40m dx-cluster spots"; break;
case "/30m": $rnd->o['30m'] = " h-menu-act"; $rnd->o['title'] = "30m dx-cluster spots"; break;
case "/20m": $rnd->o['20m'] = " h-menu-act"; $rnd->o['title'] = "20m dx-cluster spots"; break;
case "/17m": $rnd->o['17m'] = " h-menu-act"; $rnd->o['title'] = "17m dx-cluster spots"; break;
case "/15m": $rnd->o['15m'] = " h-menu-act"; $rnd->o['title'] = "15m dx-cluster spots"; break;
case "/12m": $rnd->o['12m'] = " h-menu-act"; $rnd->o['title'] = "12m dx-cluster spots"; break;
case "/10m": $rnd->o['10m'] = " h-menu-act"; $rnd->o['title'] = "10m dx-cluster spots"; break;
case "/6m": $rnd->o['6m'] = " h-menu-act"; $rnd->o['title'] = "6m dx-cluster spots"; break;
case "/4m": $rnd->o['4m'] = " h-menu-act"; $rnd->o['title'] = "4m dx-cluster spots"; break;
case "/2m": $rnd->o['2m'] = " h-menu-act"; $rnd->o['title'] = "2m dx-cluster spots"; break;
case "/70cm": $rnd->o['70cm'] = " h-menu-act"; $rnd->o['title'] = "70cm dx-cluster spots"; break;
case "/23cm": $rnd->o['23cm'] = " h-menu-act"; $rnd->o['title'] = "23cm dx-cluster spots"; break;
case "/6cm": $rnd->o['6cm'] = " h-menu-act"; $rnd->o['title'] = "6cm dx-cluster spots"; break;
case "/3cm": $rnd->o['3cm'] = " h-menu-act"; $rnd->o['title'] = "3cm dx-cluster spots"; break;
}

if(stripos($uri,"/?q=") !== FALSE){
 $call = ltrim($uri,"/?q=");
 $rnd->o['title'] = "{$call} dx-cluster spots";
}

$rnd->render('assets/index.html');
