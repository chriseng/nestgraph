<?php

$json = json_decode(file_get_contents(__DIR__ . '/cli/config.json'), true);

define('DEFAULT_HRS', 72);

$hrs = DEFAULT_HRS;
if (array_key_exists("hrs", $_GET)) {
  $hrs = $_GET["hrs"];
}

try {
  $db = new mysqli($json['db_host'], $json['db_user'], $json['db_pass'], $json['db_name']);
  if ($db->connect_error) {
    throw new Exception($db->connect_error);
  }
  if ($stmt = $db->prepare("SELECT * from data where timestamp>=DATE_SUB(NOW(), INTERVAL ? HOUR) order by timestamp")) {
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
