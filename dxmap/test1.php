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

$searchParams['index'] = 'dxspider';
$searchParams['type']  = 'spots';
$searchParams['size']  = 0;

//$searchParams['aggs']['topdx']['filter']['range']['_timestamp']['from'] = 'now-1h';
$searchParams['body']['query']['filtered']['query']['match_all'] = array();
$searchParams['body']['query']['filtered']['filter']['range']['_timestamp']['gte'] = time()*1000 - 60*60*1000;
$searchParams['body']['aggs']['topdx']['terms']['field'] = 'dx';
$searchParams['body']['aggs']['topdx']['terms']['size'] = 30;

$retDoc = $client->search($searchParams);


var_dump($retDoc);