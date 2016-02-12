<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

$time = date('r');
echo "retry: 1000\n";
$spots = file_get_contents('spots.txt');
$ar_spots = explode('*', $spots);

$out = "data: " . json_encode($ar_spots) . "\n\n";
//var_dump($ar_spots);
echo $out;

flush();
?>