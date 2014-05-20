<?php

require 'inc/config.php';
require 'inc/class.db.php';

define('DEFAULT_ID', 1);
define('DEFAULT_HRS', 72);
define('DEFAULT_DATA', "log");

$hrs = DEFAULT_HRS;
if (!empty($_GET["hrs"])) {
  $hrs = $_GET["hrs"];
}
$id = DEFAULT_ID; 
if (!empty($_GET["id"])) {
  $id = $_GET["id"];
}

$data_type = DEFAULT_DATA;
if(!empty($_GET["data"])) {
  $data_type = $_GET["data"];
}

$start_and_end = false;
if(!empty($_GET["start"]) && !empty($_GET["end"])) {
  $start_and_end = true;
  $time_start = $_GET["start"];
  $time_end = $_GET["end"];
}


$json = array();
try {
  $db = new DB($config);
  
  if($data_type == "energy")
  {
    if($stmt = $db->res->prepare("SELECT energyDate, total_heating_time, total_cooling_time, " . 
          "total_fan_cooling_time, total_humidifier_time, total_dehumidifier_time, leafs " . 
          "from energy_data where device_id=? order by energyDate"))
    {
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $stmt->bind_result($energy_date, $heating, $cooling, $fan, $humid, $dehumid, $leafs);
      
      header('Content-type: application/json');
      $i=0;
      while ($stmt->fetch()) {
       $json[$i]['timestamp'] = $energy_date;
       $json[$i]['heating'] = $heating;
       $json[$i]['cooling'] = $cooling;
       $json[$i]['fan'] = $fan;
       $json[$i]['humid'] = $humid;
       $json[$i]['dehumid'] = $dehumid;
       $json[$i]['leaf'] = $leafs;
       $i++;
      }
      print json_encode($json);
      $stmt->close();
    }
  }
  else if($data_type == "cycles")
  {
    if($stmt = $db->res->prepare("SELECT cycleDate, cycleNum, start, " . 
          "duration, type " . 
          "from cycles_data where device_id=? order by cycleDate"))
    {
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $stmt->bind_result($cycle_date, $num, $start, $duration, $type);
      
      header('Content-type: application/json');
      $i=0;
      while ($stmt->fetch()) {
       $json[$i]['timestamp'] = $cycle_date;
       $json[$i]['cycle_num'] = $num;
       $json[$i]['start'] = $start;
       $json[$i]['duration'] = $duration;
       $json[$i]['type'] = $type;
       $i++;
      }
      print json_encode($json);
      $stmt->close();
    }
  }
  else
  {
    $where_stmt = "timestamp>=DATE_SUB(NOW(), INTERVAL " . $hrs. " HOUR)";
    if($start_and_end) {
      //$where_stmt = "timestamp BETWEEN \"" . $time_start . "\" AND \"" . $time_end . "\"";
      $where_stmt = "timestamp BETWEEN " . $time_start . " AND " . $time_end;
    }
    
    $sql_query = "SELECT timestamp, heating, cooling, fan, autoAway, manualAway, leaf, target, target2, current, humidity, outsideTemperature, updated from data where device_id=? and " . $where_stmt . " order by timestamp";
    //print $sql_query;
    
    if ($stmt = $db->res->prepare( $sql_query)) {
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $stmt->bind_result($timestamp, $heating, $cooling, $fan, $autoAway, $manualAway, $leaf, $target, $target2, $current, $humidity, $outsideTemperature, $updated);
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
       $json[$i]['outsideTemperature'] = $outsideTemperature;
       $json[$i]['updated'] = $updated;
       $i++;
      }
      print json_encode($json);
      $stmt->close();
    }
  }
  $db->close();
} catch (Exception $e) {
  $errors[] = ("DB connection error! <code>" . $e->getMessage() . "</code>.");
}

?>
