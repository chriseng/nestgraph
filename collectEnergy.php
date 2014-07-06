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
        $events -> heat_temp = $nest->temperatureInUserScale($events -> heat_temp);
        $events -> cool_temp = $nest->temperatureInUserScale($events -> cool_temp);
      }
      unset($events);
    }
    unset($day);
  }
  unset($object);
  return $energy;  
}
?>
