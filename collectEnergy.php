<?php

require 'inc/config.php';
require 'nest-api-master/nest.class.php';

define('USERNAME', $config['nest_user']);
define('PASSWORD', $config['nest_pass']);

date_default_timezone_set($config['local_tz']);

function get_nest_data() {
  $nest = new Nest();
  $info = $nest->getDeviceInfo();
  $energy = $nest->getEnergyLatest();
  
  //Change nulls to 0
  foreach ($energy->days as &$day)
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
    }
    unset($events);
  }
  unset($day);
  
  return $energy;  
}

function c_to_f($c) {
  return ($c * 1.8) + 32;
}

?>