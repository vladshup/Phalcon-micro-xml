<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
$uri = htmlspecialchars($_SERVER['HTTP_REFERER']);
$uri = "/" . ltrim($uri, "http://dxmap.ru");
$hasquery = stripos($uri,"/?q=");
require '../vendor/autoload.php';


    
$client = new Elasticsearch\Client();

if($hasquery !== FALSE){
$query = ltrim($uri,"/?q=");
$searchParams['body']['query']['match']['dx'] = strtoupper($query);        
}else{
//Band filters
switch ($uri) {
    case "/":        
        break;
    case "/vlf":
        $searchParams['body']['filter']['range']['freq']['gte'] = '0.0';
        $searchParams['body']['filter']['range']['freq']['lte'] = '700.0';
        break;
    case "/160m":
        $searchParams['body']['filter']['range']['freq']['gte'] = '1800.0';
        $searchParams['body']['filter']['range']['freq']['lte'] = '2000.0';
        break;
    case "/80m":
        $searchParams['body']['filter']['range']['freq']['gte'] = '3500.0';
        $searchParams['body']['filter']['range']['freq']['lte'] = '4000.0';
        break;    
    case "/60m":
        $searchParams['body']['filter']['range']['freq']['gte'] = '5000.0';
        $searchParams['body']['filter']['range']['freq']['lte'] = '6000.0';
        break;     
    case "/40m":
        $searchParams['body']['filter']['range']['freq']['gte'] = '7000.0';
        $searchParams['body']['filter']['range']['freq']['lte'] = '7300.0';
        break;
    case "/30m":
        $searchParams['body']['filter']['range']['freq']['gte'] = '10000.0';
        $searchParams['body']['filter']['range']['freq']['lte'] = '10200.0';
        break;
    case "/20m":
        $searchParams['body']['filter']['range']['freq']['gte'] = '14000.0';
        $searchParams['body']['filter']['range']['freq']['lte'] = '14500.0';
        break;
    case "/17m":
        $searchParams['body']['filter']['range']['freq']['gte'] = '18000.0';
        $searchParams['body']['filter']['range']['freq']['lte'] = '18500.0';
        break;
    case "/15m":
        $searchParams['body']['filter']['range']['freq']['gte'] = '21000.0';
        $searchParams['body']['filter']['range']['freq']['lte'] = '21500.0';
        break;    
    case "/15m":
        $searchParams['body']['filter']['range']['freq']['gte'] = '21000.0';
        $searchParams['body']['filter']['range']['freq']['lte'] = '21500.0';
        break;  
    case "/12m":
        $searchParams['body']['filter']['range']['freq']['gte'] = '24000.0';
        $searchParams['body']['filter']['range']['freq']['lte'] = '25000.0';
        break;  
    case "/10m":
        $searchParams['body']['filter']['range']['freq']['gte'] = '28000.0';
        $searchParams['body']['filter']['range']['freq']['lte'] = '30000.0';
        break;  
    case "/6m":
        $searchParams['body']['filter']['range']['freq']['gte'] = '50000.0';
        $searchParams['body']['filter']['range']['freq']['lte'] = '56000.0';
        break;      
    case "/4m":
        $searchParams['body']['filter']['range']['freq']['gte'] = '70000.0';
        $searchParams['body']['filter']['range']['freq']['lte'] = '75000.0';
        break;      
    case "/2m":
        $searchParams['body']['filter']['range']['freq']['gte'] = '140000.0';
        $searchParams['body']['filter']['range']['freq']['lte'] = '148000.0';
        break; 
    case "/70cm":
        $searchParams['body']['filter']['range']['freq']['gte'] = '430000.0';
        $searchParams['body']['filter']['range']['freq']['lte'] = '450000.0';
        break; 
    case "/23cm":
        $searchParams['body']['filter']['range']['freq']['gte'] = '1200000.0';
        $searchParams['body']['filter']['range']['freq']['lte'] = '1300000.0';
        break; 
    case "/6cm":
        $searchParams['body']['filter']['range']['freq']['gte'] = '5000000.0';
        $searchParams['body']['filter']['range']['freq']['lte'] = '6000000.0';
        break; 
    case "/3cm":
        $searchParams['body']['filter']['range']['freq']['gte'] = '10000000.0';
        $searchParams['body']['filter']['range']['freq']['lte'] = '25000000.0';
        break;   
}    
$searchParams['body']['query']['match_all'] = array();
}

$searchParams['index'] = 'dxspider';
$searchParams['type']  = 'spots';
$searchParams['size']  = 20;
$searchParams['body']['sort']['_timestamp']['order']  = 'desc';
$retDoc = $client->search($searchParams);

foreach ($retDoc["hits"]["hits"] as $spot){
    $spot["_source"]["time"] = $spot["sort"][0];
    $spots[] = $spot["_source"];
}
foreach ($spots as $spot){
    $data[] = "\n" . str_pad($spot["spotter"].":", 9, " ")  . str_pad($spot["freq"],10," ", STR_PAD_LEFT) . "  " .  str_pad($spot["dx"], 12, " ") . htmlspecialchars(str_pad(substr($spot["info"],0,20), 20, " ")) . " " . date("H:i", $spot["time"] / 1000) . "Z";
}
$data[0] = ltrim($data[0],"\n");
//$data = $hasquery;
//var_dump($retDoc);


echo "retry: 1000\n";


$out = "data: " . json_encode($data) . "\n\n";

echo $out;
flush();
?>