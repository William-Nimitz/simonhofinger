<?php
// ignore_user_abort(true);
set_time_limit(0);
date_default_timezone_set("Europe/Berlin");
$mainUrl='https://www.kitanetz.de/';

$stateCsv = "./data/state.csv";

function getCsvResult($fileName){
    $re = array();
    $file = fopen($fileName,"r");
    while(! feof($file)) {
        array_push($re,fgetcsv($file));
    }
    fclose($file);
    return $re;
}

function getDaemon(){
    $daeV = getCsvResult("./data/daemon.csv");
    return $daeV;
}
function putDaemon($index, $runState){
    $beforeState = getDaemon();
    $fdaemon = fopen("./data/daemon.csv", "w");
    fputcsv($fdaemon, array('Script_Name', 'State', 'Controll'));
    $beforeState[$index][1] = $runState;
    $beforeState[$index][2] = 0;
    for($ii = 1; $ii < 5; $ii++) {
        fputcsv($fdaemon, $beforeState[$ii]);
    };
}
function writeLogo($con){
    
    $logTxt = "./data/log.txt";
    $file = fopen($logTxt, "a");
    fwrite($file,$con);
    fclose($file);
}

// check run state 
$runState = getDaemon();
if($runState[1][1] === "run") {
    exit;
}
putDaemon(1, "run");
$start = date("Y-m-d h:i:sa");
writeLogo("storeState.php: ".$start." Start\r\n");

// To get State list 
try {
    
    $stateList = array();
    $ch = curl_init($mainUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $html = curl_exec($ch);
    $dom = new DOMDocument();
    if(!empty($html)){
        
        @$dom->loadHtml($html);
        $section = $dom->getElementById('einleitung');
        $div = $section->getElementsByTagName('div');
        foreach ($div as $block) {
            $class=$block->getAttribute('class');
            if($class === 'block'){
                $aUrl = $block->getElementsByTagName('a');
                foreach($aUrl as $aa){
                    $uUrl = $aa->getAttribute('href');
                    $uName = str_replace('Kitaverzeichnis ', '', $aa->getAttribute('title'));
                    $arr = array($uName, $uUrl);
                    array_push($stateList,$arr);
                }
                break;
            }
        }
        curl_close($ch);
    }
} catch (Exception $exception) {
    // echo $exception->getMessage();
    writeLogo("storeState.php: ".$exception->getMessage()."\r\n");
}

try {

    $stateListB = array();
    foreach ($stateList as $state) {
        $ch = curl_init($state[1]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $html = curl_exec($ch);
        $dom = new DOMDocument();
        
        if(!empty($html)){
            
            @$dom->loadHtml($html);
            $main = $dom->getElementsByTagName('main')[0];
            $div = $main->getElementsByTagName('div');
    
            foreach ($div as $block) {
                $classB=$block->getAttribute('class');
                if($classB === 'cent'){
                    $aUrl = $block->getElementsByTagName('a')[0];
                    $uUrl = substr($aUrl->getAttribute('href'), 4);
                    array_push($stateListB,$uUrl);
                    break;
                }
            }   
        }
    }
    curl_close($ch);
    // to store state name and url in csv 
    $fState = fopen($stateCsv,"w");
    fputcsv($fState, array('State Name', 'State Url'));
    $ii = 0;
    foreach ($stateList as $line) {
        fputcsv($fState, array($line[0], $stateListB[$ii]));
        $ii++;
    }
    fclose($fState);
    $end = date("Y-m-d h:i:sa");
    putDaemon(1, "stop");
    writeLogo("storeState.php: ".$end.' End'."\r\n");
    exit;
} catch (Exception $exception) {
     writeLogo("storeState.php: ".$exception->getMessage()."\r\n");
}

?>