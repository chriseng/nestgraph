<?php

require 'inc/config.php';
require 'inc/class.db.php';
require 'collect.php';
require 'yahoo-api-master/YahooWeather.class.php';

try {
  $db = new DB($config);
  $temperature_units = strtolower($config['temperature_units']);
  
  //Yahoo now needs Oauth1 to get weather... fix later.
  $yahoo = new YahooWeather($config['yh_consumerKey'], $config['yh_consumerKeySecret'], $config['yh_applicationId'], (int)$config["local_woeid"], $temperature_units);
  $temperature = sprintf("%.02f", $yahoo->getTemperature(false));
  $humid = sprintf("%.02f", $yahoo->getHumidity(false));
  $pressure = sprintf("%.02f", $yahoo->getPressure(false));
  //echo "Temperature:" . $temperature . "\n";


  if ($result = $db->res->query("SELECT id, serial FROM devices")) {
    while ($row = mysqli_fetch_row($result)) {
      $data = get_nest_data($row[1]);
      //print_r($data);

      if (!empty($data['timestamp']))
      {
        if ($stmt = $db->res->prepare("REPLACE INTO data (device_id, timestamp, heating, cooling, fan, autoAway, manualAway, leaf, target, target2, current, humidity, outsideTemperature,outsideHumidity,outsidePressure, updated) " .
                                    "VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())"))
        {
          $stmt->bind_param("isiiiiiidddiiii", $row[0], $data['timestamp'], $data['heating'], $data['cooling'], $data['fan'], $data['autoAway'], $data['manualAway'], $data['leaf'],
                          $data['target_temp'], $data['target_temp2'],  $data['current_temp'], $data['humidity'],$temperature, $humid, $pressure);
          if (!$stmt->execute())
          {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
          }
          $stmt->close();
        }
      }
    }
    mysqli_free_result($result);
  }
  $db->close();
} catch (Exception $e) {
  $errors[] = ("DB connection error! <code>" . $e->getMessage() . "</code>.");
}

?>
