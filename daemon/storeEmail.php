<?php
// ignore_user_abort(true);
set_time_limit(0);
date_default_timezone_set("Europe/Berlin");
$plzCsv = "./data/plz.csv";
$emailCsv = "./data/email.csv";

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
// if($runState[4][1] == "run") {
//     exit;
// }
putDaemon(4, "run");
$start = date("Y-m-d h:i:sa");
writeLogo("storeEmail.php: ".$start." Start\r\n");
// To get City Url 
try {
    
    $plzList = getCsvResult($plzCsv);
    // to store daycarecenter name email in csv 
    $femail = fopen($emailCsv,"a");
    // fputcsv($femail, array('State_Name', 'City_Name', 'DaycareCenter', 'Plz', 'Email'));
    
    $plzLen = count($plzList) - 1;
    for($ii = 1; $ii < $plzLen; $ii++) {

        $runStateA = getDaemon();
        if($runStateA[4][2] === "1") {
            putDaemon(4, "stop");
            $end = date("Y-m-d h:i:sa");
            writeLogo("storeEmail.php: byUser".$end.' End'."\r\n");
            exit;
        }
        $ch = curl_init($plzList[$ii][4]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $html = curl_exec($ch);
        $dom = new DOMDocument();
        if(!empty($html)){
            @$dom->loadHtml($html);
            $main = $dom->getElementsByTagName('main')[0];
            if(!empty($main)){
                $script_tags = $main->getElementsByTagName('script');
                if($script_tags->length > 1){
                    foreach ($script_tags as $tag) {
                        $scirptType = $tag->getAttribute('type');
                        if($scirptType === 'text/javascript') {
                            $con = $tag->nodeValue;
                            $con=preg_replace("/\s+/", "", $con);
                            $conArr=explode(';',$con); 
                            $email = substr($conArr[0],4,-1).'@'.substr($conArr[1],4,-1).'.'.substr($conArr[2],4,-1);
                            fputcsv($femail, array($plzList[$ii][0], $plzList[$ii][1], $plzList[$ii][2],$plzList[$ii][3], $email));
                        }
                    }
                } else {
                    fputcsv($femail, array($plzList[$ii][0],$plzList[$ii][1],$plzList[$ii][2],$plzList[$ii][3], "No Email"));
                }
            }
        }
    }
    curl_close($ch);
    fclose($femail);
    putDaemon(4, "stop");
    $end = date("Y-m-d h:i:sa");
    writeLogo("storeEmail.php: ".$end.' End'."\r\n");
} catch (Exception $exception) {
    writeLogo("storeEmail.php: ".$exception->getMessage()."\r\n");
}

?>