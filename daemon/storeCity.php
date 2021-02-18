<?php
// ignore_user_abort(true);
set_time_limit(0);
date_default_timezone_set("Europe/Berlin");
$mainUrl='https://www.kitanetz.de/';

$stateCsv = "./data/state.csv";
$cityCsv = "./data/city.csv";

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
if($runState[2][1] === "run") {
    exit;
}
 
putDaemon(2, "run");
$start = date("Y-m-d h:i:sa");
writeLogo("storeCity.php: ".$start." Start\r\n");

// To get City List 
try {

    $stateList = getCsvResult($stateCsv);
        
    // to store city name and url in csv 
    $fCity = fopen($cityCsv,"w");
    fputcsv($fCity, array('State Name', 'City Name', 'CityUrl'));

    $stateLen = count($stateList) - 1;
    for($ii = 1; $ii < $stateLen; $ii++) {
        
        $runStateA = getDaemon();
        if($runStateA[2][2] === "1") {
            putDaemon(2, "stop");
            $end = date("Y-m-d h:i:sa");
            writeLogo("storeCity.php: byUser".$end.' End'."\r\n");
            exit;
        }
        $ch = curl_init($mainUrl.$stateList[$ii][1]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $html = curl_exec($ch);
        $dom = new DOMDocument();
        
        if(!empty($html)){
            @$dom->loadHtml($html);
            $sections = $dom->getElementsByTagName('section');
            foreach ($sections as $section) {
                $iid = $section->getAttribute('id');
                if($iid === 'Hauptteil'){
                    $aUrl = $section->getElementsByTagName('a');
                    $sameUrl = '';
                    foreach($aUrl as $aa){
                        $uUrl = $aa->getAttribute('href');
                        $uClass = $aa->getAttribute('class');
                        if(($uUrl !== $sameUrl) and ($uClass !== "button")) {
                            $uName = str_replace('Kitas im Kreis ', '', $aa->getAttribute('title'));
                            fputcsv($fCity, array($stateList[$ii][0], $uName, $uUrl));
                            $sameUrl = $uUrl;
                        }
                    }
                    break;
                }
            }
        }
    }
    curl_close($ch);
    fclose($fCity);
    putDaemon(2, "stop");
    $end = date("Y-m-d h:i:sa");
    writeLogo("storeCity.php: ".$end.' End'."\r\n");
    exit;
} catch (Exception $exception) {
    writeLogo("storeCity.php: ".$exception->getMessage()."\r\n");
}

?>