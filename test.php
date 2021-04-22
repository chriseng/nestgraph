<?php

require 'inc/config.php';
require 'nest-api-master/nest.class.php';

define('USERNAME', $config['nest_user']);
define('PASSWORD', $config['nest_pass']);
date_default_timezone_set($config['local_tz']);

$nest = new Nest();

$status = $nest->getStatus();
print_r($status);

$infos = $nest->getDeviceInfo();
print_r($infos);

stuff_we_care_about($infos);

function stuff_we_care_about($info) {
  echo "Heating             : ";
  printf("%s\n", ($info->current_state->heat == 1 ? 1 : 0));
  echo "Timestamp           : ";
  printf("%s\n", $info->network->last_connection);
  echo "Target temperature  : ";
  printf("%.02f\n", $info->target->temperature);
  echo "Current temperature : ";
  printf("%.02f\n", $info->current_state->temperature);
  echo "Current humidity    : ";
  printf("%d\n", $info->current_state->humidity);

}

function c_to_f($c) {
  return ($c * 1.8) + 32;
}

