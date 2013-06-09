<?php

require 'inc/config.php';
require 'nest-api-master/nest.class.php';

define('USERNAME', $config['nest_user']);
define('PASSWORD', $config['nest_pass']);
date_default_timezone_set($config['local_tz']);

$nest = new Nest();

$status = $nest->getStatus();
print_r($status);
echo "\n<br>\n<br>";

$infos = $nest->getDeviceInfo();
print_r($infos);
echo "\n<br>\n<br>";
stuff_we_care_about($infos);

function stuff_we_care_about($info) {
  echo "Heating             : ";
  printf("%s\n<br>", ($info->current_state->heat == 1 ? 1 : 0));
  echo "Timestamp           : ";
  printf("%s\n<br>", $info->network->last_connection);
  echo "Target temperature  : ";
  if (preg_match("/away/", $info->current_state->mode)) {
    printf("%.02f\n<br>", $info->target->temperature[0]);
  } else {
    printf("%.02f\n<br>", $info->target->temperature);
  }
  echo "Current temperature : ";
  printf("%.02f\n<br>", $info->current_state->temperature);
  echo "Current humidity    : ";
  printf("%d\n<br>", $info->current_state->humidity);

}

function c_to_f($c) {
  return ($c * 1.8) + 32;
}

