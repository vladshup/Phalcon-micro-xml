<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include_once (dirname(__FILE__) . '/lib/loaderPHPClass.php');
$client = new Elasticsearch\Client();

$data = array();
$band = array("vlf","160m","80m","60m","40m","30m","20m","17m","15m","12m","10m","6m","4m","2m","70cm","23cm","6cm","3cm");

foreach ($band as $key => $value){
$searchParams['index'] = 'dxspider';
$searchParams['type']  = 'spots';
$searchParams['body']['filter']['term']['band'] = $value;
$searchParams['body']['query']['range']['_timestamp']['gt'] = 'now-1h';
$retDoc = $client->search($searchParams);
$data["$key"] = $retDoc["hits"]["total"];
}
var_dump(json_encode($data));