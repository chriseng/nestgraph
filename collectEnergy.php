<?php

require 'inc/config.php';
require 'nest-api-master/nest.class.php';


date_default_timezone_set($config['local_tz']);

function get_nest_data($serial_number=null) {
  global $config;
  $nest = new Nest($config['nest_user'], $config['nest_pass']);
  //$info = $nest->getDeviceInfo($serial_number);
  $energy = $nest->getEnergyLatest($serial_number);
  //Change nulls to 0
  foreach($energy->objects as &$object)
  {
    $value = $object->value;
    foreach ($value->days as &$day)
    {
      foreach ($day->events as &$events)
      {
        if ( empty($events -> continuation) )
        {
          $events -> continuation = 0;
        }
        if ( empty($events -> touched_by) )
        {
          $events -> touched_by = 0;
        }
        if ( empty($events -> touched_when) )
        {
          $events -> touched_when = 0;
        }
        if ( empty($events -> touched_timezone_offset) )
        {
          $events -> touched_timezone_offset = 0;
        }
        if ( empty($events -> touched_where) )
        {
          $events -> touched_where = 0;
        }
        
        if ( empty($events -> heat_temp) )
        {
          $events -> heat_temp = 0;
        }
        else
        {
          $events -> heat_temp = $nest->temperatureInUserScale($events -> heat_temp);
        }
        
        if ( empty($events -> cool_temp) )
        {
          $events -> cool_temp = 0;
        }
        else
        {
          $events -> cool_temp = $nest->temperatureInUserScale($events -> cool_temp);
        }

      }
      unset($events);
    }
    unset($day);
  }
  unset($object);
  return $energy;
}

function compute_daily_temps(&$db, $time_start, $id)
{
    $json = array();
    
    //$where_stmt = "timestamp BETWEEN \"" . $time_start . "\" AND \"" . $time_end . "\"";
    $where_stmt = "timestamp BETWEEN " . $time_start . " AND DATE_ADD(" . $time_start . ", INTERVAL 24 HOUR)";
    
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
        
        return json_encode($json);
      }
    }
    return "";
}
?>
