<?php

require 'inc/config.php';
require 'inc/class.db.php';

define('DEFAULT_ID', 1);
define('DEFAULT_HRS', 72);

$hrs = DEFAULT_HRS; 
if (!empty($_GET["hrs"])) {
  $hrs = $_GET["hrs"];
}
$id = DEFAULT_ID; 
if (!empty($_GET["id"])) {
  $id = $_GET["id"];
}
$json = array();
try {
  $db = new DB($config);
  if ($stmt = $db->res->prepare("SELECT timestamp, heating, cooling, fan, autoAway, manualAway, leaf, target, target2, current, humidity, updated " .
                                "from data where device_id=? and timestamp>=DATE_SUB(NOW(), INTERVAL ? HOUR) order by timestamp")) {
    $stmt->bind_param("ii", $id, $hrs);
    $stmt->execute();
    $stmt->bind_result($timestamp, $heating, $cooling, $fan, $autoAway, $manualAway, $leaf, $target, $target2, $current, $humidity, $updated);
    header('Content-type: application/json');
    $i=0;
    while ($stmt->fetch()) {
     $json[$i]['timestamp'] = $timestamp;
     $json[$i]['heating'] = $heating;
     $json[$i]['cooling'] = $cooling;
     $json[$i]['fan'] = $fan;
     $json[$i]['autoAway'] = $autoAway;
     $json[$i]['manualAway'] = $manualAway;
     $json[$i]['leaf'] = $leaf;
     $json[$i]['target'] = $target;
     $json[$i]['target2'] = $target2;
     $json[$i]['current'] = $current;
     $json[$i]['humidity'] = $humidity;
     $json[$i]['updated'] = $updated;
     $i++;
    }
    print json_encode($json);
    $stmt->close();
  }
  $db->close();
} catch (Exception $e) {
  $errors[] = ("DB connection error! <code>" . $e->getMessage() . "</code>.");
}

?>
