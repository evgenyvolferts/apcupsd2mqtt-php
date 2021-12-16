# apcupsd2mqtt-php

This service allows you:
- to collect UPS status data from several systems running [apcupsd](http://www.apcupsd.org/) and publish them on an MQTT broker 
- to generate UPS sensors configuration for [Home Assistant](https://www.home-assistant.io).

## Installation

The service requires PHP version 7.4 or higher, [composer](https://getcomposer.org/download/) and [apcupsd](http://www.apcupsd.org/). Just clone the repo and install the dependencies.

```bash
cd /home/pi
git clone https://github.com/evgenyvolferts/apcupsd2mqtt-php.git
cd apcupsd2mqtt-php/
composer install
```
After that you can create your configuration file
```bash
cp ./config/config.example.json ./config/config.json
```
## Configuration file
```json
{
  "mqttHost": "192.168.1.10",
  "mqttPort": 1883,
  "mqttUser": "user",
  "mqttPassword": "password",
  "pidFile": "/tmp/apcupsd2mqtt-php.pid",
  "errorLog": "/var/log/apcupsd2mqtt-php-error.log",
  "interval": 10,
  "devices": [
    {
      "name": "ups1",
      "host": "192.168.1.2",
      "port": 3551,
      "haTopic": "homeassistant/sensor/ups1"
    },
    {
      "name": "ups2",
      "host": "192.168.1.3",
      "port": 3551,
      "haTopic": "homeassistant/sensor/ups2"
    }
  ],
  "properties": [
    "APC",
    "DATE",
    "HOSTNAME",
    "UPSNAME",
    "VERSION",
    "etc"
  ]
}
```
- you can leave `errorLog` empty if STDOUT messages suits you
- `interval` specifies the number of seconds between the start of data request cycles (can be fractional)  
- leave `haTopic` empty if you don't use [Home Assistant](https://www.home-assistant.io) - service will not create sensor configuration topics
- you can delete some of the `properties` if you want to skip them in your MQTT broker and [Home Assistant](https://www.home-assistant.io) (see full properties list in `config/config.example.json`)

## Systemd service installation
Do not forget to specify user to run service and to configure valid installation and php binary paths in the service file.
```bash
sudo cp apcupsd2mqtt-php.service /lib/systemd/system/
sudo chmod 644 /lib/systemd/system/apcupsd2mqtt-php.service
sudo systemctl daemon-reload
sudo systemctl enable apcupsd2mqtt-php.service
sudo systemctl start apcupsd2mqtt-php.service
```

## License

`evgenyvolferts/apcupsd2mqtt-php` is open-sourced software licensed under the [MIT license](LICENSE).