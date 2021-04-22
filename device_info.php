<?php

require 'inc/config.php';
require 'nest-api-master/nest.class.php';

define('USERNAME', $config['nest_user']);
define('PASSWORD', $config['nest_pass']);
date_default_timezone_set($config['local_tz']);

$nest = new Nest();

$infos = $nest->getDeviceInfo();
print_r($infos);

