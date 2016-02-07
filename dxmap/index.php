<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once (dirname(__FILE__) . '/lib/Telnet.php');
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
try {
    // These settings worked with Allied Telesis cpe:
    $t = new Net_Telnet(array(
        'host'              =>  '62.183.34.131',
        'debug'             =>  FALSE,
    ));

//Connect DX-cluster    
    $t->connect();

    
echo $t->login( array(
        'login_prompt'  => 'Please enter your call: ',
        'login_success' => '',
        'login_fail'    => '% Access denied',
        'login'         => 'rx3qfm',
        'password_prompt'   =>  '-',
        'password'      => '',
        'prompt'        => 'arc >',
        )
    );
    

//Get stream from cluster
    while ($t->online()) {
        //$data = $t->println();        
        if (($ret = $t->read_stream()) === false)
            break;
        echo $t->get_data();
    }
        
    $t->disconnect();
    // catch any buffered data
    echo $t->get_data();
}
catch (Exception $e) {
    echo "Caught Exception ('{$e->getMessage()}')\n{$e}\n";
}
exit();
