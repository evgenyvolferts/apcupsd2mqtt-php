<?php

namespace evgenyvolferts\Apcupsd2mqttPhp;

use Exception;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class Apcupsd2mqttPhp
{
    const DEFAULT_INTERVAL = 1000000;

    const ERROR_NO_CONFIG = 1;
    const ERROR_INVALID_CONFIG_JSON = 2;
    const ERROR_NO_DEVICES_CONFIGURED = 3;
    const ERROR_CANNOT_DELETE_PID_FILE = 4;
    const ERROR_MQTT = 5;
    const ERROR_APCUPSD_NOT_INSTALLED = 6;
    const ERROR_APCACCESS = 7;
    const ERROR_UNKNOWN = 99;

    public static array $propertyConfigTemplate = [
        'availability'        => [
            [
                'topic' => 'apcupsd2mqtt-php/bridge/state',
            ],
        ],
        'enabled_by_default'  => true,
        'entity_category'     => 'diagnostic',
        'icon'                => 'mdi:information',
        'unit_of_measurement' => '',
        'value_template'      => '{{ value_json.%PROPERTY_NAME% }}',
    ];

    public static array $properties = [
        'APC'       => [
            'description'         => 'Header record indicating the STATUS format revision level, the number of records that follow the APC statement, and the number of bytes that follow the record.',
            'topic_name'          => 'apc',
            'icon'                => 'mdi:information',
            'unit_of_measurement' => '',
        ],
        'DATE'      => [
            'description'         => 'The date and time that the information was last obtained from the UPS.',
            'topic_name'          => 'date',
            'icon'                => 'mdi:calendar-clock',
            'unit_of_measurement' => '',
        ],
        'HOSTNAME'  => [
            'description'         => 'The name of the machine that collected the UPS data.',
            'topic_name'          => 'hostname',
            'icon'                => 'mdi:information',
            'unit_of_measurement' => '',
        ],
        'UPSNAME'   => [
            'description'         => 'The name of the UPS as stored in the EEPROM or in the UPSNAME directive in the configuration file.',
            'topic_name'          => 'ups_name',
            'icon'                => 'mdi:information',
            'unit_of_measurement' => '',
        ],
        'VERSION'   => [
            'description'         => 'The apcupsd release number, build date, and platform.',
            'topic_name'          => 'apcupsd_version',
            'icon'                => 'mdi:information',
            'unit_of_measurement' => '',
        ],
        'CABLE'     => [
            'description'         => 'The cable as specified in the configuration file (UPSCABLE).',
            'topic_name'          => 'ups_cable',
            'icon'                => 'mdi:cable-data',
            'unit_of_measurement' => '',
        ],
        'MODEL'     => [
            'description'         => 'The UPS model as derived from information from the UPS.',
            'topic_name'          => 'ups_model',
            'icon'                => 'mdi:information',
            'unit_of_measurement' => '',
        ],
        'UPSMODE'   => [
            'description'         => 'The mode in which apcupsd is operating as specified in the configuration file (UPSMODE)',
            'topic_name'          => 'ups_mode',
            'icon'                => 'mdi:information',
            'unit_of_measurement' => '',
        ],
        'STARTTIME' => [
            'description'         => 'The time/date that apcupsd was started.',
            'topic_name'          => 'apcupsd_started',
            'icon'                => 'mdi:calendar-clock',
            'unit_of_measurement' => '',
        ],
        'STATUS'    => [
            'description'         => 'The current status of the UPS (ONLINE, ONBATT, etc.)',
            'topic_name'          => 'ups_status',
            'icon'                => 'mdi:information',
            'unit_of_measurement' => '',
        ],
        'LINEV'     => [
            'description'         => 'The current line voltage as returned by the UPS.',
            'topic_name'          => 'line_voltage',
            'icon'                => 'mdi:flash',
            'unit_of_measurement' => 'V',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'LOADPCT'   => [
            'description'         => 'The percentage of load capacity as estimated by the UPS.',
            'topic_name'          => 'load_percentage',
            'icon'                => 'mdi:gauge',
            'unit_of_measurement' => '%',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'BCHARGE'   => [
            'description'         => 'The percentage charge on the batteries.',
            'topic_name'          => 'batteries_charge',
            'icon'                => 'mdi:gauge',
            'unit_of_measurement' => '%',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'TIMELEFT'  => [
            'description'         => 'The remaining runtime left on batteries as estimated by the UPS.',
            'topic_name'          => 'time_left',
            'icon'                => 'mdi:av-timer',
            'unit_of_measurement' => 'minutes',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'MBATTCHG'  => [
            'description'         => 'If the battery charge percentage (BCHARGE) drops below this value, apcupsd will shutdown your system. Value is set in the configuration file (BATTERYLEVEL)',
            'topic_name'          => 'charge_to_shutdown',
            'icon'                => 'mdi:gauge',
            'unit_of_measurement' => '%',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'MINTIMEL'  => [
            'description'         => 'apcupsd will shutdown your system if the remaining runtime equals or is below this point. Value is set in the configuration file (MINUTES)',
            'topic_name'          => 'minutes_to_shutdown',
            'icon'                => 'mdi:av-timer',
            'unit_of_measurement' => 'minutes',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'MAXTIME'   => [
            'description'         => 'apcupsd will shutdown your system if the time on batteries exceeds this value. A value of zero disables the feature. Value is set in the configuration file (TIMEOUT)',
            'topic_name'          => 'max_time_on_batteries',
            'icon'                => 'mdi:av-timer',
            'unit_of_measurement' => 'seconds',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'MAXLINEV'  => [
            'description'         => 'The maximum line voltage since the UPS was started, as reported by the UPS',
            'topic_name'          => 'max_line_voltage',
            'icon'                => 'mdi:flash',
            'unit_of_measurement' => 'V',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'MINLINEV'  => [
            'description'         => 'The minimum line voltage since the UPS was started, as returned by the UPS',
            'topic_name'          => 'min_line_voltage',
            'icon'                => 'mdi:flash',
            'unit_of_measurement' => 'V',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'OUTPUTV'   => [
            'description'         => 'The voltage the UPS is supplying to your equipment',
            'topic_name'          => 'output_voltage',
            'icon'                => 'mdi:flash',
            'unit_of_measurement' => 'V',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'SENSE'     => [
            'description'         => 'The sensitivity level of the UPS to line voltage fluctuations.',
            'topic_name'          => 'sensitivity_level',
            'icon'                => 'mdi:information',
            'unit_of_measurement' => '',
        ],
        'DWAKE'     => [
            'description'         => 'The amount of time the UPS will wait before restoring power to your equipment after a power off condition when the power is restored.',
            'topic_name'          => 'time_to_restore_power',
            'icon'                => 'mdi:av-timer',
            'unit_of_measurement' => 'seconds',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'DSHUTD'    => [
            'description'         => 'The grace delay that the UPS gives after receiving a power down command from apcupsd before it powers off your equipment.',
            'topic_name'          => 'delay_after_shutdown_command',
            'icon'                => 'mdi:av-timer',
            'unit_of_measurement' => 'seconds',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'DLOWBATT'  => [
            'description'         => 'The remaining runtime below which the UPS sends the low battery signal. At this point apcupsd will force an immediate emergency shutdown.',
            'topic_name'          => 'remaining_runtime_to_shutdown',
            'icon'                => 'mdi:av-timer',
            'unit_of_measurement' => '',
        ],
        'LOTRANS'   => [
            'description'         => 'The line voltage below which the UPS will switch to batteries.',
            'topic_name'          => 'low_voltage',
            'icon'                => 'mdi:flash',
            'unit_of_measurement' => 'V',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'HITRANS'   => [
            'description'         => 'The line voltage above which the UPS will switch to batteries.',
            'topic_name'          => 'high_voltage',
            'icon'                => 'mdi:flash',
            'unit_of_measurement' => 'V',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'RETPCT'    => [
            'description'         => 'The percentage charge that the batteries must have after a power off condition before the UPS will restore power to your equipment.',
            'topic_name'          => 'min_charge_to_restore',
            'icon'                => 'mdi:gauge',
            'unit_of_measurement' => '%',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'ITEMP'     => [
            'description'         => 'Internal UPS temperature as supplied by the UPS.',
            'topic_name'          => 'internal_temperature',
            'icon'                => 'mdi:thermometer',
            'unit_of_measurement' => '°C',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'ALARMDEL'  => [
            'description'         => 'The delay period for the UPS alarm.',
            'topic_name'          => 'alarm_delay',
            'icon'                => 'mdi:av-timer',
            'unit_of_measurement' => '',
        ],
        'BATTV'     => [
            'description'         => 'Battery voltage as supplied by the UPS.',
            'topic_name'          => 'battery_voltage',
            'icon'                => 'mdi:flash',
            'unit_of_measurement' => 'V',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'LINEFREQ'  => [
            'description'         => 'Line frequency in hertz as given by the UPS.',
            'topic_name'          => 'line_frequency',
            'icon'                => 'mdi:sine-wave',
            'unit_of_measurement' => 'Hz',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'LASTXFER'  => [
            'description'         => 'The reason for the last transfer to batteries.',
            'topic_name'          => 'last_transfer_reason',
            'icon'                => 'mdi:information',
            'unit_of_measurement' => '',
        ],
        'NUMXFERS'  => [
            'description'         => 'The number of transfers to batteries since apcupsd startup.',
            'topic_name'          => 'number_of_transfers',
            'icon'                => 'mdi:information',
            'unit_of_measurement' => '',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'XONBATT'   => [
            'description'         => 'Time and date of last transfer to batteries, or N/A.',
            'topic_name'          => 'last_transfer_to_batteries_datetime',
            'icon'                => 'mdi:calendar-clock',
            'unit_of_measurement' => '',
        ],
        'TONBATT'   => [
            'description'         => 'Time in seconds currently on batteries, or 0.',
            'topic_name'          => 'seconds_on_batteries',
            'icon'                => 'mdi:av-timer',
            'unit_of_measurement' => 'seconds',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'CUMONBATT' => [
            'description'         => 'Total (cumulative) time on batteries in seconds since apcupsd startup.',
            'topic_name'          => 'total_seconds_on_batteries',
            'icon'                => 'mdi:av-timer',
            'unit_of_measurement' => 'seconds',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'XOFFBATT'  => [
            'description'         => 'Time and date of last transfer from batteries, or N/A.',
            'topic_name'          => 'last_transfer_from_batteries_datetime',
            'icon'                => 'mdi:calendar-clock',
            'unit_of_measurement' => '',
        ],
        'SELFTEST'  => [
            'description'         => 'The results of the last self test, and may have the following values:',
            'topic_name'          => 'self_test_result',
            'icon'                => 'mdi:information',
            'unit_of_measurement' => '',
        ],
        'STESTI'    => [
            'description'         => 'The interval in hours between automatic self tests.',
            'topic_name'          => 'self_test_interval',
            'icon'                => 'mdi:av-timer',
            'unit_of_measurement' => '',
        ],
        'STATFLAG'  => [
            'description'         => 'Status flag. English version is given by STATUS.',
            'topic_name'          => 'status_flag',
            'icon'                => 'mdi:information',
            'unit_of_measurement' => '',
        ],
        'DIPSW'     => [
            'description'         => 'The current dip switch settings on UPSes that have them.',
            'topic_name'          => 'dip_switch',
            'icon'                => 'mdi:information',
            'unit_of_measurement' => '',
        ],
        'REG1'      => [
            'description'         => 'The value from the UPS fault register 1.',
            'topic_name'          => 'register_1',
            'icon'                => 'mdi:information',
            'unit_of_measurement' => '',
        ],
        'REG2'      => [
            'description'         => 'The value from the UPS fault register 2.',
            'topic_name'          => 'register_2',
            'icon'                => 'mdi:information',
            'unit_of_measurement' => '',
        ],
        'REG3'      => [
            'description'         => 'The value from the UPS fault register 3.',
            'topic_name'          => 'register_3',
            'icon'                => 'mdi:information',
            'unit_of_measurement' => '',
        ],
        'MANDATE'   => [
            'description'         => 'The date the UPS was manufactured.',
            'topic_name'          => 'manufacturing_date',
            'icon'                => 'mdi:calendar',
            'unit_of_measurement' => '',
        ],
        'SERIALNO'  => [
            'description'         => 'The UPS serial number.',
            'topic_name'          => 'serial_number',
            'icon'                => 'mdi:information',
            'unit_of_measurement' => '',
        ],
        'BATTDATE'  => [
            'description'         => 'The date that batteries were last replaced.',
            'topic_name'          => 'batteries_replace_date',
            'icon'                => 'mdi:calendar-clock',
            'unit_of_measurement' => '',
        ],
        'NOMOUTV'   => [
            'description'         => 'The output voltage that the UPS will attempt to supply when on battery power.',
            'topic_name'          => 'output_voltage_on_batteries',
            'icon'                => 'mdi:flash',
            'unit_of_measurement' => 'V',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'NOMINV'    => [
            'description'         => 'The input voltage that the UPS is configured to expect.',
            'topic_name'          => 'expected_input_voltage',
            'icon'                => 'mdi:flash',
            'unit_of_measurement' => 'V',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'NOMBATTV'  => [
            'description'         => 'The nominal battery voltage.',
            'topic_name'          => 'nominal_battery_voltage',
            'icon'                => 'mdi:flash',
            'unit_of_measurement' => 'V',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'NOMPOWER'  => [
            'description'         => 'The maximum power in Watts that the UPS is designed to supply.',
            'topic_name'          => 'max_power',
            'icon'                => 'mdi:flash',
            'unit_of_measurement' => 'W',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'HUMIDITY'  => [
            'description'         => 'The humidity as measured by the UPS.',
            'topic_name'          => 'humidity',
            'icon'                => 'mdi:water-percent',
            'unit_of_measurement' => '%',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'AMBTEMP'   => [
            'description'         => 'The ambient temperature as measured by the UPS.',
            'topic_name'          => 'ambient_temperature',
            'icon'                => 'mdi:thermometer',
            'unit_of_measurement' => '°C',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'EXTBATTS'  => [
            'description'         => 'The number of external batteries as defined by the user. A correct number here helps the UPS compute the remaining runtime more accurately.',
            'topic_name'          => 'external_batteries_number',
            'icon'                => 'mdi:information',
            'unit_of_measurement' => '',
        ],
        'BADBATTS'  => [
            'description'         => 'The number of bad battery packs.',
            'topic_name'          => 'bad_batteries_number',
            'icon'                => 'mdi:information',
            'unit_of_measurement' => '',
        ],
        'FIRMWARE'  => [
            'description'         => 'The firmware revision number as reported by the UPS.',
            'topic_name'          => 'firmware',
            'icon'                => 'mdi:information',
            'unit_of_measurement' => '',
        ],
        'APCMODEL'  => [
            'description'         => 'The old APC model identification code.',
            'topic_name'          => 'model_identification_code',
            'icon'                => 'mdi:information',
            'unit_of_measurement' => '',
        ],
        'END APC'   => [
            'description'         => 'The time and date that the STATUS record was written.',
            'topic_name'          => 'record_datetime',
            'icon'                => 'mdi:calendar-clock',
            'unit_of_measurement' => '',
        ],
    ];

    private bool $stop;
    private bool $sensorsConfigNeeded;
    private int $executionInterval;
    private array $config;
    private MqttClient $mqtt;

    /**
     * @param string $configFilename
     */
    public function __construct(string $configFilename = '')
    {
        if (!file_exists($configFilename)) {
            $this->terminateWithError(
                'Cannot find config/config.json',
                self::ERROR_NO_CONFIG
            );
        }

        $this->config = json_decode(file_get_contents($configFilename), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->terminateWithError(
                'Invalid JSON in config/config.json',
                self::ERROR_INVALID_CONFIG_JSON
            );
        }

        if (empty($this->config['devices'])) {
            $this->terminateWithError(
                'No devices configured',
                self::ERROR_NO_DEVICES_CONFIGURED
            );
        }

        if ($this->isDaemonActive($this->config['pidFile'])) {
            $this->terminateWithError('Daemon already exists!');
        }

        pcntl_signal(SIGTERM, [$this, 'signalHandler']);
        pcntl_signal(SIGINT, [$this, 'signalHandler']);

        file_put_contents($this->config['pidFile'], getmypid());

        $this->stop = false;
        $this->executionInterval = ($this->config['interval'] ?? self::DEFAULT_INTERVAL) * 1000000;
        $this->sensorsConfigNeeded = true;

        try {
            $this->mqtt = new MqttClient($this->config['mqttHost'], $this->config['mqttPort']);
            $connectionSettings = (new ConnectionSettings)
                ->setConnectTimeout(3)
                ->setUsername($this->config['mqttUser'])
                ->setPassword($this->config['mqttPassword']);
            $this->mqtt->connect($connectionSettings, true);
            $this->mqtt->publish(
                'apcupsd2mqtt-php/bridge/state',
                'online',
                0,
                true
            );
        } catch (Exception $e) {
            $this->terminateWithError(
                'MQTT exception: ' . $e->getMessage(),
                self::ERROR_MQTT
            );
        }
    }

    /**
     * @param string $host
     * @param int $port
     * @return array
     */
    public static function collect(string $host = '127.0.0.1', int $port = 3551): array
    {
        $output = [];
        exec("apcaccess -h {$host}:{$port} 2>&1", $output);
        if (in_array(count($output), [0, 1])) {
            return [
                'success' => false,
                'error'   => $output[0] ?? '',
                'data'    => [],
            ];
        }

        $data = [];
        foreach ($output as $line) {
            if (strpos($line, ':') === false) {
                continue;
            }

            $tempArray = explode(':', $line);
            $name = array_shift($tempArray);
            $value = implode(':', $tempArray);
            $data[trim($name)] = trim($value);
        }

        return [
            'success' => true,
            'error'   => '',
            'data'    => $data,
        ];
    }

    public function __destruct()
    {
        try {
            $this->mqtt->publish(
                'apcupsd2mqtt-php/bridge/state',
                'offline'
            );

            $this->mqtt->disconnect();
        } catch (Exception $e) {
            $this->terminateWithError(
                'MQTT exception: ' . $e->getMessage(),
                self::ERROR_MQTT
            );
        }
    }

    /**
     * @return void
     */
    public function run()
    {

        declare(ticks=1);

        while (!$this->stop) {
            $runningTime = -microtime(true);

            foreach ($this->config['devices'] as $device) {
                if (empty($device['host'])) {
                    continue;
                }
                $result = self::collect($device['host'], $device['port']);

                if ($result['success'] === true) {
                    if (
                        empty($result['data']['SERIALNO'])
                        || empty($result['data']['MODEL'])
                    ) {
                        continue;
                    }

                    $deviceSerial = strtolower($result['data']['SERIALNO']);

                    $sensorData = [];

                    foreach ($result['data'] as $key => $value) {
                        if (!in_array($key, array_keys(self::$properties))) {
                            continue;
                        }

                        $sensorData[self::$properties[$key]['topic_name']] = $value;
                    }

                    // publish all sensors current state
                    try {
                        $this->mqtt->publish(
                            'apcupsd2mqtt-php/' . $deviceSerial,
                            json_encode($sensorData, JSON_UNESCAPED_UNICODE)
                        );
                    } catch (Exception $e) {
                        $this->terminateWithError(
                            'MQTT exception: ' . $e->getMessage(),
                            self::ERROR_MQTT
                        );
                    }

                    // no need to publish sensor config if Home Assistant topic is not set
                    if ($this->sensorsConfigNeeded && !empty($device['haTopic'])) {
                        $deviceConfig = [
                            'identifiers'  => [
                                'apcupsd2mqtt_php_' . $deviceSerial,
                            ],
                            'manufacturer' => 'APC',
                            'model'        => $result['data']['MODEL'],
                            'name'         => $device['name'],
                        ];

                        foreach ($result['data'] as $key => $value) {
                            // unknown property
                            if (!in_array($key, array_keys(self::$properties))) {
                                continue;
                            }

                            // property is not in allowed list in config
                            if (!empty($this->config['properties']) && !in_array($key, $this->config['properties'])) {
                                continue;
                            }

                            $sensorConfig = self::$propertyConfigTemplate;
                            $sensorConfig['device'] = $deviceConfig;
                            if (isset(self::$properties[$key]['icon'])) {
                                $sensorConfig['icon'] = self::$properties[$key]['icon'];
                            }
                            $sensorConfig['json_attributes_topic'] = 'apcupsd2mqtt-php/' . $deviceSerial;
                            $sensorConfig['name'] = strtolower($device['name']) . ' ' . self::$properties[$key]['topic_name'];
                            $sensorConfig['state_topic'] = 'apcupsd2mqtt-php/' . $deviceSerial;
                            $sensorConfig['unique_id'] = 'apcupsd2mqtt_php_' . $deviceSerial . '_' . self::$properties[$key]['topic_name'];
                            if (isset(self::$properties[$key]['unit_of_measurement'])) {
                                $sensorConfig['unit_of_measurement'] = self::$properties[$key]['unit_of_measurement'];
                            }
                            if (isset(self::$properties[$key]['value_template'])) {
                                $sensorConfig['value_template'] = str_replace(
                                    '%PROPERTY_NAME%',
                                    self::$properties[$key]['topic_name'],
                                    self::$properties[$key]['value_template']
                                );
                            } else {
                                $sensorConfig['value_template'] = str_replace(
                                    '%PROPERTY_NAME%',
                                    self::$properties[$key]['topic_name'],
                                    $sensorConfig['value_template']
                                );
                            }

                            // publish sensor config to make it available in Home Assistant
                            try {
                                $this->mqtt->publish(
                                    strtolower($device['haTopic']) . '/' . self::$properties[$key]['topic_name'] . '/config',
                                    json_encode($sensorConfig, JSON_UNESCAPED_UNICODE)
                                );
                            } catch (Exception $e) {
                                $this->terminateWithError(
                                    'MQTT exception: ' . $e->getMessage(),
                                    self::ERROR_MQTT
                                );
                            }
                        }
                    }
                } else {
                    if (strpos($result['error'], 'apcaccess: not found') !== false) {
                        $this->terminateWithError(
                            'Package apcupsd is not installed',
                            self::ERROR_APCUPSD_NOT_INSTALLED
                        );
                    } elseif (!empty($result['error'])) {
                        $this->terminateWithError(
                            'Error running apcaccess: ' . $result['error'],
                            self::ERROR_APCACCESS
                        );
                    } else {
                        $this->terminateWithError(
                            'Unknown error running apcaccess (' .
                            implode(':', [$device['host'], $device['port']]) . ')',
                            self::ERROR_UNKNOWN
                        );
                    }
                }
            }

            // no need to save sensors config each interval - only on start
            if ($this->sensorsConfigNeeded) {
                $this->sensorsConfigNeeded = false;
            }

            $runningTime += microtime(true);
            $delay = $this->executionInterval - round($runningTime * 1000000);
            if ($delay > 0) {
                usleep($delay);
            }
        }
    }

    /**
     * @param int $signal
     * @return void
     */
    private function signalHandler(int $signal)
    {
        switch ($signal) {
            case SIGTERM:
            case SIGINT:
                $this->stop = true;
                break;
        }
    }

    /**
     * @param string $pidFile
     * @return bool
     */
    private function isDaemonActive(string $pidFile = ''): bool
    {
        if (empty($pidFile)) {
            return false;
        }

        if (is_file($pidFile)) {
            $pid = file_get_contents($pidFile);
            if (posix_kill($pid, 0)) {
                return true;
            } elseif (!unlink($pidFile)) {
                $this->terminateWithError(
                    'Cannot delete PID file, process ' . $pid . ' unavailable',
                    self::ERROR_CANNOT_DELETE_PID_FILE
                );
            }
        }
        return false;
    }

    /**
     * @param string $errorMessage
     * @param int $exitCode
     * @return void
     */
    private function terminateWithError(string $errorMessage = '', int $exitCode = 0)
    {
        if (!empty($this->config['errorLog'])) {
            file_put_contents(
                $this->config['errorLog'],
                date('[Y-m-d H:i:s] ') . $errorMessage . PHP_EOL,
                FILE_APPEND
            );
            exit($exitCode);
        } else {
            echo $errorMessage . PHP_EOL;
            exit($exitCode);
        }
    }

}