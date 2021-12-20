<?php

require __DIR__ . '/vendor/autoload.php';

use evgenyvolferts\Apcupsd2mqttPhp\Apcupsd2mqttPhp;

$daemon = new Apcupsd2mqttPhp(__DIR__ . '/config/config.json');
echo $daemon->generateCustomizationYaml();
echo PHP_EOL;