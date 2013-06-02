<?php

require 'inc/config.php';
require 'inc/class.db.php';
require 'collect.php';

try {
  $db = new DB($config);
  $data = get_nest_data();
  if (!empty($data['timestamp'])) {
    if ($stmt = $db->res->prepare("REPLACE INTO data (timestamp, heating, cooling, fan, autoAway, manualAway, leaf, target, current, humidity, updated) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())")) {
      $stmt->bind_param("siiiiiiddi", $data['timestamp'], $data['heating'], $data['cooling'], $data['fan'], $data['autoAway'], $data['manualAway'], $data['leaf'], $data['target_temp'], $data['current_temp'], $data['humidity']);
      $stmt->execute();
      $stmt->close();
    }
  }
  $db->close();
} catch (Exception $e) {
  $errors[] = ("DB connection error! <code>" . $e->getMessage() . "</code>.");
}

?>