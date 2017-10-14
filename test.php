<?php

require 'inc/config.php';
require 'nest-api-master/nest.class.php';

date_default_timezone_set($config['local_tz']);

$nest = new Nest($config['nest_user'], $config['nest_pass']);

$devices= $nest->getDevices();
foreach($devices as $serial) {
   $infos = $nest->getDeviceInfo($serial);
   stuff_we_care_about($infos);
}

function stuff_we_care_about($info) {
  echo "Name             : ";
  printf("%s\n<br>", $info->name);
  echo "Heating             : ";
  printf("%s\n<br>", ($info->current_state->heat == 1 ? 1 : 0));
  echo "Cooling             : ";
  printf("%s\n<br>", ($info->current_state->ac == 1 ? 1 : 0));
  echo "Timestamp           : ";
  printf("%s\n<br>", $info->network->last_connection);
  echo "Target temperature  : ";
  if (preg_match("/away/", $info->current_state->mode) || preg_match("/range/", $info->current_state->mode)) {
    printf("%.02f %.02f\n<br>", $info->target->temperature[0], $info->target->temperature[1]);
  } else {
    printf("%.02f\n<br>", $info->target->temperature);
  }
  echo "Current temperature : ";
  printf("%.02f\n<br>", $info->current_state->temperature);
  echo "Current humidity    : ";
  printf("%d\n<br>\n", $info->current_state->humidity);

}

