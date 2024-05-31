<?php

require __DIR__ . '/vendor/autoload.php';

use evgenyvolferts\Apcupsd2mqttPhp\Apcupsd2mqttPhp;

$daemon = new Apcupsd2mqttPhp(__DIR__ . '/config/config.json');

$usePrefix = !isset($argv[1]) || $argv[1] != 'without-prefix';

echo $daemon->generateCustomizationYaml($usePrefix);
echo PHP_EOL;