<?php

require 'inc/config.php';
require 'inc/class.db.php';
require 'collectEnergy.php';

try {
  $db = new DB($config);
  $energy = get_nest_data();
  
  foreach ($energy->days as $day)
  {
    foreach ($day->events as $key => $events)
    {
      if ($stmt = $db->res->prepare("REPLACE INTO events_data (eventNum, eventDate, start, end, type, touched_by, touched_when, touched_timezone_offset, touched_where, heat_temp, cool_temp, continuation, event_touched_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")) 
      {
        $stmt->bind_param("isiiiiiiiiiii", $key, $day->day, $events->start, $events->end, $events->type, $events->touched_by, $events->touched_when, $events-> touched_timezone_offset, $events->touched_where, $events->heat_temp, $events->cool_temp, $events->continuation, $events->event_touched_by);
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
      if ($stmt = $db->res->prepare("REPLACE INTO cycles_data (cycleNum, cycleDate, start, duration, type) VALUES (?,?,?,?,?)")) 
      {
        $stmt->bind_param("isiii", $key, $day->day, $cycles->start, $cycles->duration, $cycles->type);
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
   
    if ($stmt = $db->res->prepare("REPLACE INTO energy_data (energyDate, device_timezone_offset, total_heating_time, total_cooling_time, total_fan_cooling_time, total_humidifier_time, total_dehumidifier_time, leafs, whodunit, recent_avg_used, usage_over_avg) VALUES (?,?,?,?,?,?,?,?,?,?,?)")) 
    {
      $stmt->bind_param("siiiiiiiiii", $day->day, $day->device_timezone_offset, $day->total_heating_time, $day->total_cooling_time, $day->total_fan_cooling_time, $day->total_humidifier_time, $day->total_dehumidifier_time, $day->leafs, $day->whodunit, $day->recent_avg_used, $day->usage_over_avg);
      $stmt->execute();      
      $stmt->close();
    }
  }
  $db->close();
} catch (Exception $e) {
  $errors[] = ("DB connection error! <code>" . $e->getMessage() . "</code>.");
}

?>