<?php

require 'inc/config.php';
require 'inc/class.db.php';

define('DEFAULT_HRS', 72);

$hrs = DEFAULT_HRS; 
if (array_key_exists("hrs", $_GET)) {
  $hrs = $_GET["hrs"];
}

try {
  $db = new DB($config);
  if ($stmt = $db->res->prepare("SELECT * from data where timestamp>=DATE_SUB(NOW(), INTERVAL ? HOUR) order by timestamp")) {
    $stmt->bind_param("i", $hrs);
    $stmt->execute();
    $stmt->bind_result($timestamp, $heating, $target, $current, $humidity, $updated);
    header("Content-type: text/tab-separated-values");
    print "timestamp\theating\ttarget\tcurrent\thumidity\tupdated\n";
    while ($stmt->fetch()) {
      print implode("\t", array($timestamp, $heating, $target, $current, $humidity, $updated)) . "\n";
    }
    $stmt->close();
  }
  $db->close();
} catch (Exception $e) {
  $errors[] = ("DB connection error! <code>" . $e->getMessage() . "</code>.");
}

?>
