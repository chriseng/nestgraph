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

try {
  $db = new DB($config);
  if ($stmt = $db->res->prepare("SELECT * from data where device_id=? and timestamp>=DATE_SUB(NOW(), INTERVAL ? HOUR) order by timestamp")) {
    $stmt->bind_param("ii", $id, $hrs);
    $stmt->execute();
    $stmt->bind_result($device_id, $timestamp, $heating, $cooling, $fan, $autoAway, $manualAway, $leaf, $target, $current, $humidity, $updated);
    header("Content-type: text/tab-separated-values");
    print "timestamp\theating\tcooling\tfan\tautoAway\tmanualAway\tleaf\ttarget\tcurrent\thumidity\tupdated\n";
    while ($stmt->fetch()) {
      print implode("\t", array($timestamp, $heating, $cooling, $fan, $autoAway, $manualAway, $leaf, $target, $current, $humidity, $updated)) . "\n";
    }
    $stmt->close();
  }
  $db->close();
} catch (Exception $e) {
  $errors[] = ("DB connection error! <code>" . $e->getMessage() . "</code>.");
}

?>
