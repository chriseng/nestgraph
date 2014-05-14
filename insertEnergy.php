<?php

require 'inc/config.php';
require 'inc/class.db.php';
require 'collectEnergy.php';

try {
  $db = new DB($config);
  
   if ($result = $db->res->query("SELECT id, serial FROM devices")) {
    while ($row = mysqli_fetch_row($result)) 
    {
      $energy = get_nest_data($row[1]);
      
      //print_r($energy);
      
      foreach($energy->objects as $object)
      {
        $value = $object->value;
        foreach ($value->days as $day)
        {
          foreach ($day->events as $key => $events)
          {
            if ($stmt = $db->res->prepare("REPLACE INTO events_data (device_id, eventNum, eventDate, start, end, type, touched_by, touched_when, touched_timezone_offset, touched_where, heat_temp, cool_temp, continuation, event_touched_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)")) 
            {
              $stmt->bind_param("iisiiiiiiiiiii", $row[0], $key, $day->day, $events->start, $events->end, $events->type, $events->touched_by, $events->touched_when, $events-> touched_timezone_offset, $events->touched_where, $events->heat_temp, $events->cool_temp, $events->continuation, $events->event_touched_by);
              $stmt->execute();
              $stmt->close();
            }
          }

          if ($stmt = $db->res->prepare("DELETE FROM events_data where eventNum > $key AND eventDate = '$day->day'")) 
          {
            $stmt->execute();
            $stmt->close();
          }
          unset($key);
          
          foreach ($day->cycles as $key => $cycles)
          {
            if ($stmt = $db->res->prepare("REPLACE INTO cycles_data (device_id, cycleNum, cycleDate, start, duration, type) VALUES (?,?,?,?,?,?)")) 
            {
              $stmt->bind_param("iisiii", $row[0], $key, $day->day, $cycles->start, $cycles->duration, $cycles->type);
              $stmt->execute();
              $stmt->close();
            }
          }

          if ($stmt = $db->res->prepare("DELETE FROM cycles_data where cycleNum > $key AND cycleDate = '$day->day'")) 
          {
            $stmt->execute();
            $stmt->close();
          }
          unset($key);
         
          if ($stmt = $db->res->prepare("REPLACE INTO energy_data (device_id, energyDate, device_timezone_offset, total_heating_time, total_cooling_time, total_fan_cooling_time, total_humidifier_time, total_dehumidifier_time, leafs, whodunit, recent_avg_used, usage_over_avg) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")) 
          {
            $stmt->bind_param("isiiiiiiiiii", $row[0],  $day->day, $day->device_timezone_offset, $day->total_heating_time, $day->total_cooling_time, $day->total_fan_cooling_time, $day->total_humidifier_time, $day->total_dehumidifier_time, $day->leafs, $day->whodunit, $day->recent_avg_used, $day->usage_over_avg);
            $stmt->execute();      
            $stmt->close();
          }
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