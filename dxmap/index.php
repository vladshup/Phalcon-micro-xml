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
        'debug'             =>  TRUE,
    ));

//Connect DX-cluster    
    $t->connect();
    $t->echomode('none');
    
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
$text = array('','','','','','','','','','');
$i = 0;
    while ($t->online()) {
        $i++;
        //$data = $t->println();        
        if (($ret = $t->read_stream()) === false)
            break;
        $spot = $t->get_data();
        $spot = str_replace(array("\r\n", "\n", "\r"), '', $spot);
        if(!empty($spot)){
        
        echo $spot . "\n";

        $text[9] = $text[8];
        $text[8] = $text[7];
        $text[7] = $text[6];
        $text[6] = $text[5];
        $text[5] = $text[4];
        $text[4] = $text[3];
        $text[3] = $text[2];
        $text[2] = $text[1];
        $text[1] = $text[0];
        $text[0] = $spot."*";
        
        $spots = implode("\n", $text);
        file_put_contents('spots.txt', $spots);

          
              
        
        }
        $t->put_data('\n');
        
        
    }
        
    $t->disconnect();
    // catch any buffered data
    echo $t->get_data();
}
catch (Exception $e) {
    echo "Caught Exception ('{$e->getMessage()}')\n{$e}\n";
}
exit();
