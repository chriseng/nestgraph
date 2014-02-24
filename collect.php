<?php

require 'inc/config.php';
require 'nest-api-master/nest.class.php';

define('USERNAME', $config['nest_user']);
define('PASSWORD', $config['nest_pass']);

date_default_timezone_set($config['local_tz']);

function get_nest_data($serial_number=null) {
  $nest = new Nest();
  $info = $nest->getDeviceInfo($serial_number);
  
  if (preg_match("/away/", $info->current_state->mode) || preg_match("/range/", $info->current_state->mode)) {
      $targetTemp = $info->target->temperature[0];
      $targetTemp2 = $info->target->temperature[1];
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
                'target_temp2' => (isset($targetTemp2) ? sprintf("%.02f", $targetTemp2) : null),
                'current_temp' => sprintf("%.02f", $info->current_state->temperature),
                'humidity'     => $info->current_state->humidity
               );
  return $data;
}

?>
