<?php
// ignore_user_abort(true);
set_time_limit(0);
// $mainUrl='https://www.kitanetz.de/';
// $endpoint = "bezirke/bezirke.php?";
date_default_timezone_set("Europe/Berlin");

$cityCsv = "./data/city.csv";
$plzCsv = "./data/plz.csv";
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
if($runState[3][1] === "run") {
    exit;
}
 
putDaemon(3, "run");
$start = date("Y-m-d h:i:sa");
writeLogo("storePlz.php: ".$start." Start\r\n");

// To get daycare center List 
try {

    $cityList = getCsvResult($cityCsv);
        
    // to store city name and url in csv 
    $fPlz = fopen($plzCsv,"w");
    fputcsv($fPlz, array('State_Name', 'City_Name', 'Daycare', 'Plz', 'Daycare Url'));
    
    $cityLen = count($cityList) - 1;
    for($ii = 1; $ii < $cityLen; $ii++) {
        $runStateA = getDaemon();
        if($runStateA[3][2] === "1") {
            putDaemon(3, "stop");
            $end = date("Y-m-d h:i:sa");
            writeLogo("storePlz.php: byUser".$end.' End'."\r\n");
            exit;
        }
	//    $curlUrl = $mainUrl.$endpoint."land=".$cityList[$ii][2]."&kreis=".$cityList[$ii][3];
       $ch = curl_init($cityList[$ii][2]);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       $html = curl_exec($ch);
       $dom = new DOMDocument();
       if(!empty($html)){
		   @$dom->loadHtml($html);
		   $table = $dom->getElementsByTagName('table')[0];
		   $ttr = $table->getElementsByTagName('tr');
		   foreach ($ttr as $tr) {
			   $ttd = $tr->getElementsByTagName('td');
			   foreach ($ttd as $td) {
				   $iid = $td->getAttribute('headers');
				   if($iid === 'header2') {
					   $plz = substr($td->nodeValue, 0, -1);
				   } elseif($iid === 'header3') {
					   $plzName = $td->childNodes[1]->nodeValue;
					   $plzUrl = $td->childNodes[1]->getAttribute('href');
				   } else {

				   }
			   }
			   if(isset($plzName)){
				   fputcsv($fPlz, array($cityList[$ii][0], $cityList[$ii][1], $plzName, $plz, $plzUrl));
			   }
            }
        }  
    }
	curl_close($ch);
    fclose($fPlz);
    putDaemon(3, "stop");
    $end = date("Y-m-d h:i:sa");
    writeLogo("storePlz.php: ".$end.' End'."\r\n");
    exit;
} catch (Exception $exception) {
    writeLogo("storePlz.php: ".$exception->getMessage()."\r\n");
}

?>