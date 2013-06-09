<?php

require 'inc/config.php';
require 'nest-api-master/nest.class.php';

define('USERNAME', $config['nest_user']);
define('PASSWORD', $config['nest_pass']);

date_default_timezone_set($config['local_tz']);

function get_nest_data() {
  $nest = new Nest();
  $info = $nest->getDeviceInfo();
  
  if (preg_match("/away/", $info->current_state->mode) || preg_match("/range/", $info->current_state->mode)) {
    if ($info->current_state->temperature > $info->target->temperature[1]) {
      //Hotter then upper temp
      $targetTemp = $info->target->temperature[1];
    }
    else if ($info->current_state->temperature < $info->target->temperature[0]) {
      //Colder then lower temp
      $targetTemp = $info->target->temperature[0];
    }
    else {
      if (($info->target->temperature[1] - $info->current_state->temperature) <
          ($info->current_state->temperature - $info->target->temperature[0]))
      {
        //Closer to upper temp
        $targetTemp = $info->target->temperature[1];
      }
      else
      {
        //Closer to lower temp
      $targetTemp = $info->target->temperature[0];
      }
    }
  }
  else {
    $targetTemp = $info->target->temperature;
  }

  $data = array('heating'      => ($info->current_state->heat == 1 ? 1 : 0),
                'cooling'      => ($info->current_state->ac == 1 ? 1 : 0),
                'fan'          => ($info->current_state->fan == 1 ? 1 : 0),
                'autoAway'     => ($info->current_state->auto_away == 1 ? 1 : ($info->current_state->auto_away == -1 ? -1 : 0)),
                'manualAway'   => ($info->current_state->manual_away == 1 ? 1 : 0),
                'leaf'         => ($info->current_state->leaf == 1 ? 1 : 0),
                'timestamp'    => $info->network->last_connection,
                'target_temp'  => sprintf("%.02f", $targetTemp),
                'current_temp' => sprintf("%.02f", $info->current_state->temperature),
                'humidity'     => $info->current_state->humidity
               );
  return $data;
}

function c_to_f($c) {
  return ($c * 1.8) + 32;
}

?>