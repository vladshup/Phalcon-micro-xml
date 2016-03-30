<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
//xml file name
mb_internal_encoding ("UTF-8");
$dbname = "ya_phalcon";
$dbuser = "ya_phalcon";
$dbpassword = "uv3qfm";
$dbhost = "localhost";


/* Создаем соединение */
$conn = mysqli_connect($dbhost, $dbuser, $dbpassword, $dbname);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
} 

/* изменение набора символов на utf8 */
if (!mysqli_set_charset($conn, "utf8")) {
    printf("Ошибка при загрузке набора символов utf8: %s\n", mysqli_error($conn));
} else {
    printf("Текущий набор символов: %s\n", mysqli_character_set_name($conn));
}


$base = 'base.xml';



//source url
//lasumka - source company
$url = "http://export.admitad.com/ru/webmaster/websites/130405/products/export_adv_products/?user=altena&code=c76f2d2e9c&feed_id=3943&format=xml";

$xmlfeed = file_get_contents($url);

//validate and save
if(is_valid_xml($xmlfeed)){
    file_put_contents($base, $xmlfeed);
}


//xml validator
function is_valid_xml ( $xml ) {
    libxml_use_internal_errors( true );
     
    $doc = new DOMDocument('1.0', 'utf-8');
     
    $doc->loadXML( $xml );
     
    $errors = libxml_get_errors();
     
    return empty( $errors );
}



$xml = simplexml_load_file('base.xml');

//Categories update
foreach ($xml->shop->categories->category as $cat) {
    $id = $cat['id'];
    $name = mysql_escape_string(htmlspecialchars(trim($cat)));
    if (!empty($cat['parentId'])){
        $parentid = $cat['parentId'];    
    }else{
        $parentid = 0;
    }

    $uri = "/" . str2url($cat) . "-" . $id . "/";


    $sql = "INSERT INTO categories (id,name,parentid,uri) VALUES ('$id','$name','$parentid','$uri') ON DUPLICATE KEY UPDATE timestamp=VALUES(Timestamp)";
    mysqli_query($conn, $sql) or die (mysqli_error($conn));
    
}

//Offers update
foreach ($xml->shop->offers->offer as $offer) {
    $id = $offer['id'];
    $available = $offer['available']; 
    $categoryid = $offer->categoryId;
    $name = mysql_escape_string(htmlspecialchars(trim($offer->name)));
    $description = mysql_escape_string(htmlspecialchars(trim($offer->description)));
    $adurl = $offer->url;
    $picture = $offer->picture;
    $vendor = $offer->vendor;
    $vendorcode = $offer->vendorCode;

    $uri = "/" . str2url($offer->name) . "/";

 
    $sql = "INSERT INTO offers (id,available,categoryid,name,description,adurl,picture,vendor,vendorcode,uri)"
            . " VALUES ('$id','$available','$categoryid','$name','$description','$adurl','$picture','$vendor','$vendorcode','$uri') "
            . "ON DUPLICATE KEY UPDATE timestamp=VALUES(Timestamp)";
    mysqli_query($conn, $sql) or die (mysqli_error($conn));
    
}


mysqli_close($conn);



function rus2translit($string) {
    $converter = array(
        'а' => 'a',   'б' => 'b',   'в' => 'v',
        'г' => 'g',   'д' => 'd',   'е' => 'e',
        'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
        'и' => 'i',   'й' => 'y',   'к' => 'k',
        'л' => 'l',   'м' => 'm',   'н' => 'n',
        'о' => 'o',   'п' => 'p',   'р' => 'r',
        'с' => 's',   'т' => 't',   'у' => 'u',
        'ф' => 'f',   'х' => 'h',   'ц' => 'c',
        'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
        'ь' => '',    'ы' => 'y',   'ъ' => '',
        'э' => 'e',   'ю' => 'yu',  'я' => 'ya',
        
        'А' => 'A',   'Б' => 'B',   'В' => 'V',
        'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
        'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
        'И' => 'I',   'Й' => 'Y',   'К' => 'K',
        'Л' => 'L',   'М' => 'M',   'Н' => 'N',
        'О' => 'O',   'П' => 'P',   'Р' => 'R',
        'С' => 'S',   'Т' => 'T',   'У' => 'U',
        'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
        'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
        'Ь' => '',  'Ы' => 'Y',   'Ъ' => '',
        'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
    );
    return strtr($string, $converter);
}
function str2url($str) {
    // переводим в транслит
    $str = rus2translit($str);
    // в нижний регистр
    $str = strtolower($str);
    // заменям все ненужное нам на "-"
    $str = preg_replace('~[^-a-z0-9_]+~u', '-', $str);
    // удаляем начальные и конечные '-'
    $str = trim($str, "-");
    return $str;
}
