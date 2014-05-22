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
          "total_fan_cooling_time, total_humidifier_time, total_dehumidifier_time, leafs, daily_temp_avg, daily_temp_min, daily_temp_max " . 
          "from energy_data where device_id=? order by energyDate"))
    {
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $stmt->bind_result($energy_date, $heating, $cooling, $fan, $humid, $dehumid, $leafs, $temp_avg, $temp_min, $temp_max);
      
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
       $json[$i]['temperature_avg'] = $temp_avg;
       $json[$i]['temperature_min'] = $temp_min;
       $json[$i]['temperature_max'] = $temp_max;
       $i++;
      }
      print json_encode($json);
      $stmt->close();
    }
  }
  else if($data_type == "dailyTemp")
  {
    if($start_and_end) {
      //$where_stmt = "timestamp BETWEEN \"" . $time_start . "\" AND \"" . $time_end . "\"";
      $where_stmt = "timestamp BETWEEN " . $time_start . " AND " . $time_end;
      
      $sql_query = "SELECT outsideTemperature from data where device_id=? and " . $where_stmt . " order by timestamp";
      //print $sql_query;
      
      if ($stmt = $db->res->prepare( $sql_query)) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($outsideTemperature);
        header('Content-type: application/json');
        $i=0;
        $temp_min = 1000; //initial value that temp will always be less than
        $temp_max = -1000;
        $temp_total = 0;
        while ($stmt->fetch()) {
         $temp_total += $outsideTemperature;
         $temp_min = min($temp_min, $outsideTemperature);
         $temp_max = max($temp_max, $outsideTemperature);
         $i++;
        }
        $stmt->close();
        if($i > 0)
        {
          $temp_avg = $temp_total/$i;
          $temp_avg = sprintf("%.02f", $temp_avg);
          $temp_min = sprintf("%.02f", $temp_min);
          $temp_max = sprintf("%.02f", $temp_max);
          
          $json['temperature_avg'] = $temp_avg;
          $json['temperature_min'] = $temp_min;
          $json['temperature_max'] = $temp_max;
                  
          //print json_encode($json);
          $insert_time = str_replace('"', '', $time_start);
          $insert_time = $insert_time . ' 00:00:00'; //Make a new time string (append all 0s for time after date
          
          $sql_query = "UPDATE energy_data SET daily_temp_avg=?, daily_temp_min=?, daily_temp_max=? " .
              "WHERE device_id=? and energyDate=?";
          //print $sql_query;
          if ($stmt = $db->res->prepare($sql_query)) 
          {
            //print "Time: $insert_time \n";
            $stmt->bind_param("iiiis", $temp_avg, $temp_min, $temp_max, $id, $insert_time );
            if (!$stmt->execute()) 
            {
              echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
            }
            $stmt->close();
          }
          
          print json_encode($json);
        }
      }
    }
  }
  else if($data_type == "cycles")
  {
     $where_stmt = "cycleDate>=DATE_SUB(NOW(), INTERVAL " . $hrs. " HOUR)";
    if($start_and_end) {
      //$where_stmt = "timestamp BETWEEN \"" . $time_start . "\" AND \"" . $time_end . "\"";
      $where_stmt = "cycleDate BETWEEN " . $time_start . " AND " . $time_end;
    }
    
    $sql_query = "SELECT cycleDate, cycleNum, start, duration, type from cycles_data where device_id=? and " . $where_stmt . " order by cycleDate";
    //print $sql_query;
    
    if($stmt = $db->res->prepare( $sql_query)) {
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
    
    $sql_query = "SELECT timestamp, heating, cooling, fan, autoAway, manualAway, leaf, target, target2, current, humidity, outsideTemperature, outsideHumidity, outsidePressure, updated from data where device_id=? and " . $where_stmt . " order by timestamp";
    //print $sql_query;
    
    if ($stmt = $db->res->prepare( $sql_query)) {
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $stmt->bind_result($timestamp, $heating, $cooling, $fan, $autoAway, $manualAway, $leaf, $target, $target2, $current, $humidity, $outsideTemperature, $outsideHumidity, $outsidePressure, $updated);
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
       $json[$i]['outsideHumidity'] = $outsideHumidity;
       $json[$i]['outsidePressure'] = $outsidePressure;
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
