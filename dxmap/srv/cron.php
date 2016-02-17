<?php

if(file_exists ("/home/ya/web/dxmap.ru/public_html/assets/img/north-aurora.jpg" )){
    $ttl = time() - filectime ("/home/ya/web/dxmap.ru/public_html/assets/img/north-aurora.jpg");
    if($ttl > 600){
$north = file_get_contents("http://services.swpc.noaa.gov/images/animations/ovation-north/latest.png");
file_put_contents("/home/ya/web/dxmap.ru/public_html/assets/img/north-aurora.jpg", $north);
$south = file_get_contents("http://services.swpc.noaa.gov/images/animations/ovation-south/latest.png");
file_put_contents("/home/ya/web/dxmap.ru/public_html/assets/img/south-aurora.jpg", $south); 
    }
}else{
$north = file_get_contents("http://services.swpc.noaa.gov/images/animations/ovation-north/latest.png");
file_put_contents("/home/ya/web/dxmap.ru/public_html/assets/img/north-aurora.jpg", $north);
$south = file_get_contents("http://services.swpc.noaa.gov/images/animations/ovation-south/latest.png");
file_put_contents("/home/ya/web/dxmap.ru/public_html/assets/img/south-aurora.jpg", $south); 
}