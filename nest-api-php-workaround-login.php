<?php

echo "\nInstructions:\n";
echo "  - Login to https://home.nest.com in your browser\n";
echo "  - Once logged in, using the same tab, go to https://home.nest.com/session\n";
echo "  - Copy-paste the text (JSON) here (then press ENTER):\n\n";
$json = readline();
$o = json_decode($json);

echo "\nThanks!\n\n";
$username = readline("What is your Nest username: ");
$password = readline("What is your Nest password: ");

$cache_file = sys_get_temp_dir() . '/nest_php_cache_' . md5($username . $password);
echo "\nWill create cache file at $cache_file ...\n";

$vars = array(
    'transport_url' => $o->urls->transport_url,
    'access_token' => $o->access_token,
    'user' => $o->user,
    'userid' => $o->userid,
    'cache_expiration' => strtotime($o->expires_in)
);

file_put_contents($cache_file, serialize($vars));
echo "Done.\n";
echo "Access token will expire on $o->expires_in. You will need to re-execute this script before then.\n";
