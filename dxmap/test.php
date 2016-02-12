<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once (dirname(__FILE__) . '/lib/myTelnet.php');

//Config
$host = '62.183.34.131';
$port = '23';
$login_string = 'Please enter your call: ';
$login = 'rx3qfm';
$password = '';

$cisco = new Cisco($host, $password, $login, 10, $login_string);
$cisco->connect();