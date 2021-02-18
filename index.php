<?php
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
    $daeV = getCsvResult("./daemon/data/daemon.csv");
    return $daeV;
}
$runState = getDaemon();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitas Email</title>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th>Script Name</th>
                <th>Script State</th>
                <th>Script Contrll</th>
            </tr>
        </thead>
        <tbody>
        <?php
            for ($i = 0; $i < 4; $i++) {
        ?>
           <tr>
                <td><?php echo $runState[$i][0]; ?></td>
                <td><?php echo $runState[$i][1]; ?></td>
                <td><?php echo $runState[$i][2]; ?></td>
            </tr>
        <?php
            } 
        ?>
        </tbody>
    </table>
</body>
</html>