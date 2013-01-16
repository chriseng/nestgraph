<?php

require 'inc/config.php';
require 'nest-api-master/nest.class.php';

define('USERNAME', $config['nest_user']);
define('PASSWORD', $config['nest_pass']);

date_default_timezone_set($config['local_tz']);

function get_nest_data() {
  $nest = new Nest();
  $info = $nest->getDeviceInfo();
  $data = array('heating'      => ($info->current_state->heat == 1 ? 1 : 0),
		'timestamp'    => $info->network->last_connection,
		'target_temp'  => sprintf("%.02f", (preg_match("/away/", $info->current_state->mode) ? 
						    c_to_f($info->target->temperature[0]) : c_to_f($info->target->temperature))),
		'current_temp' => sprintf("%.02f", c_to_f($info->current_state->temperature)),
		'humidity'     => $info->current_state->humidity
		);
  return $data;
}

function c_to_f($c) {
  return ($c * 1.8) + 32;
}

?>