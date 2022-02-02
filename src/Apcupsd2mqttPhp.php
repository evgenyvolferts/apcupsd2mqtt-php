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
            'description'   => 'Header record indicating the STATUS format revision level, the number of ' .
                'records that follow the APC statement, and the number of bytes that follow the record.',
            'topic_name'    => 'apc',
            'friendly_name' => 'Header',
        ],
        'DATE'      => [
            'description'   => 'The date and time that the information was last obtained from the UPS.',
            'topic_name'    => 'date',
            'friendly_name' => 'Last information',
            'icon'          => 'mdi:calendar-clock',
        ],
        'HOSTNAME'  => [
            'description'   => 'The name of the machine that collected the UPS data.',
            'topic_name'    => 'hostname',
            'friendly_name' => 'Hostname',
        ],
        'UPSNAME'   => [
            'description'   => 'The name of the UPS as stored in the EEPROM or in the UPSNAME directive in the ' .
                'configuration file.',
            'topic_name'    => 'ups_name',
            'friendly_name' => 'Name',
        ],
        'VERSION'   => [
            'description'   => 'The apcupsd release number, build date, and platform.',
            'topic_name'    => 'apcupsd_version',
            'friendly_name' => 'Apcupsd version',
        ],
        'CABLE'     => [
            'description'   => 'The cable as specified in the configuration file (UPSCABLE).',
            'topic_name'    => 'ups_cable',
            'friendly_name' => 'Cable type',
            'icon'          => 'mdi:cable-data',
        ],
        'MODEL'     => [
            'description'   => 'The UPS model as derived from information from the UPS.',
            'topic_name'    => 'ups_model',
            'friendly_name' => 'Model',
        ],
        'UPSMODE'   => [
            'description'   => 'The mode in which apcupsd is operating as specified in the configuration ' .
                'file (UPSMODE)',
            'topic_name'    => 'ups_mode',
            'friendly_name' => 'Mode',
        ],
        'STARTTIME' => [
            'description'   => 'The time/date that apcupsd was started.',
            'topic_name'    => 'apcupsd_started',
            'friendly_name' => 'Apcupsd started',
            'icon'          => 'mdi:calendar-clock',
        ],
        'STATUS'    => [
            'description'   => 'The current status of the UPS (ONLINE, ONBATT, etc.)',
            'topic_name'    => 'ups_status',
            'friendly_name' => 'Status',
        ],
        'LINEV'     => [
            'description'         => 'The current line voltage as returned by the UPS.',
            'topic_name'          => 'line_voltage',
            'friendly_name'       => 'Input Voltage',
            'icon'                => 'mdi:flash',
            'unit_of_measurement' => 'V',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'LOADPCT'   => [
            'description'         => 'The percentage of load capacity as estimated by the UPS.',
            'topic_name'          => 'load_percentage',
            'friendly_name'       => 'Load',
            'icon'                => 'mdi:gauge',
            'unit_of_measurement' => '%',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'BCHARGE'   => [
            'description'         => 'The percentage charge on the batteries.',
            'topic_name'          => 'batteries_charge',
            'friendly_name'       => 'Batteries charge',
            'icon'                => 'mdi:gauge',
            'unit_of_measurement' => '%',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'TIMELEFT'  => [
            'description'         => 'The remaining runtime left on batteries as estimated by the UPS.',
            'topic_name'          => 'time_left',
            'friendly_name'       => 'Time left',
            'icon'                => 'mdi:av-timer',
            'unit_of_measurement' => 'minutes',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'MBATTCHG'  => [
            'description'         => 'If the battery charge percentage (BCHARGE) drops below this value, apcupsd ' .
                'will shutdown your system. Value is set in the configuration file (BATTERYLEVEL)',
            'topic_name'          => 'charge_to_shutdown',
            'friendly_name'       => 'Charge to shutdown',
            'icon'                => 'mdi:gauge',
            'unit_of_measurement' => '%',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'MINTIMEL'  => [
            'description'         => 'apcupsd will shutdown your system if the remaining runtime equals or is below ' .
                'this point. Value is set in the configuration file (MINUTES)',
            'topic_name'          => 'minutes_to_shutdown',
            'friendly_name'       => 'Minutes to shutdown',
            'icon'                => 'mdi:av-timer',
            'unit_of_measurement' => 'minutes',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'MAXTIME'   => [
            'description'         => 'apcupsd will shutdown your system if the time on batteries exceeds this value. ' .
                'A value of zero disables the feature. Value is set in the configuration file (TIMEOUT)',
            'topic_name'          => 'max_time_on_batteries',
            'friendly_name'       => 'Max time on batteries',
            'icon'                => 'mdi:av-timer',
            'unit_of_measurement' => 'seconds',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'MAXLINEV'  => [
            'description'         => 'The maximum line voltage since the UPS was started, as reported by the UPS',
            'topic_name'          => 'max_line_voltage',
            'friendly_name'       => 'Max input voltage',
            'icon'                => 'mdi:flash',
            'unit_of_measurement' => 'V',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'MINLINEV'  => [
            'description'         => 'The minimum line voltage since the UPS was started, as returned by the UPS',
            'topic_name'          => 'min_line_voltage',
            'friendly_name'       => 'Min input voltage',
            'icon'                => 'mdi:flash',
            'unit_of_measurement' => 'V',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'OUTPUTV'   => [
            'description'         => 'The voltage the UPS is supplying to your equipment',
            'topic_name'          => 'output_voltage',
            'friendly_name'       => 'Output voltage',
            'icon'                => 'mdi:flash',
            'unit_of_measurement' => 'V',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'SENSE'     => [
            'description'   => 'The sensitivity level of the UPS to line voltage fluctuations.',
            'topic_name'    => 'sensitivity_level',
            'friendly_name' => 'Sensitivity level',
            'icon'          => 'mdi:signal-cellular-3',
        ],
        'DWAKE'     => [
            'description'         => 'The amount of time the UPS will wait before restoring power to your equipment ' .
                'after a power off condition when the power is restored.',
            'topic_name'          => 'time_to_restore_power',
            'friendly_name'       => 'Time to restore power',
            'icon'                => 'mdi:av-timer',
            'unit_of_measurement' => 'seconds',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'DSHUTD'    => [
            'description'         => 'The grace delay that the UPS gives after receiving a power down command from ' .
                'apcupsd before it powers off your equipment.',
            'topic_name'          => 'delay_after_shutdown_command',
            'friendly_name'       => 'Delay after shutdown command',
            'icon'                => 'mdi:av-timer',
            'unit_of_measurement' => 'seconds',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'DLOWBATT'  => [
            'description'   => 'The remaining runtime below which the UPS sends the low battery signal. ' .
                'At this point apcupsd will force an immediate emergency shutdown.',
            'topic_name'    => 'remaining_runtime_to_shutdown',
            'friendly_name' => 'Remaining runtime to shutdown',
            'icon'          => 'mdi:av-timer',
        ],
        'LOTRANS'   => [
            'description'         => 'The line voltage below which the UPS will switch to batteries.',
            'topic_name'          => 'low_voltage',
            'friendly_name'       => 'Low input voltage',
            'icon'                => 'mdi:flash',
            'unit_of_measurement' => 'V',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'HITRANS'   => [
            'description'         => 'The line voltage above which the UPS will switch to batteries.',
            'topic_name'          => 'high_voltage',
            'friendly_name'       => 'High input voltage',
            'icon'                => 'mdi:flash',
            'unit_of_measurement' => 'V',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'RETPCT'    => [
            'description'         => 'The percentage charge that the batteries must have after a power off condition ' .
                'before the UPS will restore power to your equipment.',
            'topic_name'          => 'min_charge_to_restore',
            'friendly_name'       => 'Min charge to restore',
            'icon'                => 'mdi:gauge',
            'unit_of_measurement' => '%',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'ITEMP'     => [
            'description'         => 'Internal UPS temperature as supplied by the UPS.',
            'topic_name'          => 'internal_temperature',
            'friendly_name'       => 'Internal temperature',
            'icon'                => 'mdi:thermometer',
            'unit_of_measurement' => '°C',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'ALARMDEL'  => [
            'description'   => 'The delay period for the UPS alarm.',
            'topic_name'    => 'alarm_delay',
            'friendly_name' => 'Alarm delay',
            'icon'          => 'mdi:av-timer',
        ],
        'BATTV'     => [
            'description'         => 'Battery voltage as supplied by the UPS.',
            'topic_name'          => 'battery_voltage',
            'friendly_name'       => 'Battery voltage',
            'icon'                => 'mdi:flash',
            'unit_of_measurement' => 'V',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'LINEFREQ'  => [
            'description'         => 'Line frequency in hertz as given by the UPS.',
            'topic_name'          => 'line_frequency',
            'friendly_name'       => 'Line frequency',
            'icon'                => 'mdi:sine-wave',
            'unit_of_measurement' => 'Hz',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'LASTXFER'  => [
            'description'   => 'The reason for the last transfer to batteries.',
            'topic_name'    => 'last_transfer_reason',
            'friendly_name' => 'Last transfer reason',
        ],
        'NUMXFERS'  => [
            'description'    => 'The number of transfers to batteries since apcupsd startup.',
            'topic_name'     => 'number_of_transfers',
            'friendly_name'  => 'Number of transfers',
            'value_template' => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'XONBATT'   => [
            'description'   => 'Time and date of last transfer to batteries, or N/A.',
            'topic_name'    => 'last_transfer_to_batteries_datetime',
            'friendly_name' => 'Last transfer to batteries',
            'icon'          => 'mdi:calendar-clock',
        ],
        'TONBATT'   => [
            'description'         => 'Time in seconds currently on batteries, or 0.',
            'topic_name'          => 'seconds_on_batteries',
            'friendly_name'       => 'Currently on batteries',
            'icon'                => 'mdi:av-timer',
            'unit_of_measurement' => 'seconds',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'CUMONBATT' => [
            'description'         => 'Total (cumulative) time on batteries in seconds since apcupsd startup.',
            'topic_name'          => 'total_seconds_on_batteries',
            'friendly_name'       => 'Total on batteries',
            'icon'                => 'mdi:av-timer',
            'unit_of_measurement' => 'seconds',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'XOFFBATT'  => [
            'description'   => 'Time and date of last transfer from batteries, or N/A.',
            'topic_name'    => 'last_transfer_from_batteries_datetime',
            'friendly_name' => 'Last transfer from batteries',
            'icon'          => 'mdi:calendar-clock',
        ],
        'SELFTEST'  => [
            'description'   => 'The results of the last self test, and may have the following values:',
            'topic_name'    => 'self_test_result',
            'friendly_name' => 'Self test result',
        ],
        'STESTI'    => [
            'description'   => 'The interval in hours between automatic self tests.',
            'topic_name'    => 'self_test_interval',
            'friendly_name' => 'Self test interval (hours)',
            'icon'          => 'mdi:av-timer',
        ],
        'STATFLAG'  => [
            'description'   => 'Status flag. English version is given by STATUS.',
            'topic_name'    => 'status_flag',
            'friendly_name' => 'Status flag',
        ],
        'DIPSW'     => [
            'description'   => 'The current dip switch settings on UPSes that have them.',
            'topic_name'    => 'dip_switch',
            'friendly_name' => 'Current dip switch settings',
        ],
        'REG1'      => [
            'description'   => 'The value from the UPS fault register 1.',
            'topic_name'    => 'register_1',
            'friendly_name' => 'Register 1',
        ],
        'REG2'      => [
            'description'   => 'The value from the UPS fault register 2.',
            'topic_name'    => 'register_2',
            'friendly_name' => 'Register 2',
        ],
        'REG3'      => [
            'description'   => 'The value from the UPS fault register 3.',
            'topic_name'    => 'register_3',
            'friendly_name' => 'Register 3',
        ],
        'MANDATE'   => [
            'description'   => 'The date the UPS was manufactured.',
            'topic_name'    => 'manufacturing_date',
            'friendly_name' => 'Manufacturing date',
            'icon'          => 'mdi:calendar',
        ],
        'SERIALNO'  => [
            'description'   => 'The UPS serial number.',
            'topic_name'    => 'serial_number',
            'friendly_name' => 'Serial number',
        ],
        'BATTDATE'  => [
            'description'   => 'The date that batteries were last replaced.',
            'topic_name'    => 'batteries_replace_date',
            'friendly_name' => 'Batteries replaced',
            'icon'          => 'mdi:calendar-clock',
        ],
        'NOMOUTV'   => [
            'description'         => 'The output voltage that the UPS will attempt to supply when on battery power.',
            'topic_name'          => 'output_voltage_on_batteries',
            'friendly_name'       => 'Output voltage on batteries',
            'icon'                => 'mdi:flash',
            'unit_of_measurement' => 'V',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'NOMINV'    => [
            'description'         => 'The input voltage that the UPS is configured to expect.',
            'topic_name'          => 'expected_input_voltage',
            'friendly_name'       => 'Expected input voltage',
            'icon'                => 'mdi:flash',
            'unit_of_measurement' => 'V',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'NOMBATTV'  => [
            'description'         => 'The nominal battery voltage.',
            'topic_name'          => 'nominal_battery_voltage',
            'friendly_name'       => 'Nominal battery voltage',
            'icon'                => 'mdi:flash',
            'unit_of_measurement' => 'V',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'NOMPOWER'  => [
            'description'         => 'The maximum power in Watts that the UPS is designed to supply.',
            'topic_name'          => 'max_power',
            'friendly_name'       => 'Max power',
            'icon'                => 'mdi:flash',
            'unit_of_measurement' => 'W',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | int }}',
        ],
        'HUMIDITY'  => [
            'description'         => 'The humidity as measured by the UPS.',
            'topic_name'          => 'humidity',
            'friendly_name'       => 'Humidity',
            'icon'                => 'mdi:water-percent',
            'unit_of_measurement' => '%',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'AMBTEMP'   => [
            'description'         => 'The ambient temperature as measured by the UPS.',
            'topic_name'          => 'ambient_temperature',
            'friendly_name'       => 'Ambient temperature',
            'icon'                => 'mdi:thermometer',
            'unit_of_measurement' => '°C',
            'value_template'      => '{{ value_json.%PROPERTY_NAME%.split()[0] | float }}',
        ],
        'EXTBATTS'  => [
            'description'   => 'The number of external batteries as defined by the user. A correct number here ' .
                'helps the UPS compute the remaining runtime more accurately.',
            'topic_name'    => 'external_batteries_number',
            'friendly_name' => 'External batteries number',
        ],
        'BADBATTS'  => [
            'description'   => 'The number of bad battery packs.',
            'topic_name'    => 'bad_batteries_number',
            'friendly_name' => 'Bad batteries number',
        ],
        'FIRMWARE'  => [
            'description'   => 'The firmware revision number as reported by the UPS.',
            'topic_name'    => 'firmware',
            'friendly_name' => 'Firmware',
        ],
        'APCMODEL'  => [
            'description'   => 'The old APC model identification code.',
            'topic_name'    => 'model_identification_code',
            'friendly_name' => 'Model identification code',
        ],
        'END APC'   => [
            'description'   => 'The time and date that the STATUS record was written.',
            'topic_name'    => 'record_datetime',
            'friendly_name' => 'Record timestamp',
            'icon'          => 'mdi:calendar-clock',
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
     * @return string
     */
    public function generateCustomizationYaml(): string
    {
        $yaml = 'homeassistant:' . PHP_EOL;
        $yaml .= '  customize:' . PHP_EOL;

        foreach ($this->config['devices'] as $device) {
            foreach ($this->config['properties'] as $property) {
                if (empty(self::$properties[$property]['friendly_name'])) {
                    continue;
                }
                $yaml .= '    sensor.' . strtolower($device['name']) . '_' . self::$properties[$property]['topic_name'] . ':' . PHP_EOL;
                $yaml .= '      friendly_name: \'' . self::$properties[$property]['friendly_name'] . '\'' . PHP_EOL;
            }
        }

        return $yaml;
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
                        foreach ($sensorData as $topicName => $value) {
                            $this->mqtt->publish(
                                'apcupsd2mqtt-php/' . $deviceSerial . '/' . $topicName,
                                json_encode(['value' => $value], JSON_UNESCAPED_UNICODE)
                            );
                        }

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
                            $sensorConfig['json_attributes_topic'] = 'apcupsd2mqtt-php/' . $deviceSerial . '/' . self::$properties[$key]['topic_name'];
                            $sensorConfig['name'] = strtolower($device['name']) . ' ' . self::$properties[$key]['topic_name'];
                            $sensorConfig['state_topic'] = 'apcupsd2mqtt-php/' . $deviceSerial . '/' . self::$properties[$key]['topic_name'];
                            $sensorConfig['unique_id'] = 'apcupsd2mqtt_php_' . $deviceSerial . '_' . self::$properties[$key]['topic_name'];
                            if (isset(self::$properties[$key]['unit_of_measurement'])) {
                                $sensorConfig['unit_of_measurement'] = self::$properties[$key]['unit_of_measurement'];
                            }
                            if (isset(self::$properties[$key]['value_template'])) {
                                $sensorConfig['value_template'] = str_replace(
                                    '%PROPERTY_NAME%',
                                    'value',
                                    self::$properties[$key]['value_template']
                                );
                            } else {
                                $sensorConfig['value_template'] = str_replace(
                                    '%PROPERTY_NAME%',
                                    'value',
                                    $sensorConfig['value_template']
                                );
                            }

                            // publish sensor config to make it available in Home Assistant
                            try {
                                $this->mqtt->publish(
                                    strtolower($device['haTopic']) . '/' . self::$properties[$key]['topic_name'] . '/config',
                                    ''
                                );
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
                    } elseif (
                        strpos($result['error'], 'Connection refused') !== false
                        || strpos($result['error'], 'No route to host') !== false
                    ) {
                        $this->logError($result['error']);
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
     * @return void
     */
    private function logError(string $errorMessage = '')
    {
        if (!empty($this->config['errorLog'])) {
            file_put_contents(
                $this->config['errorLog'],
                date('[Y-m-d H:i:s] ') . $errorMessage . PHP_EOL,
                FILE_APPEND
            );
        } else {
            echo $errorMessage . PHP_EOL;
        }
    }

    /**
     * @param string $errorMessage
     * @param int $exitCode
     * @return void
     */
    private function terminateWithError(string $errorMessage = '', int $exitCode = 0)
    {
        $this->logError($errorMessage);
        exit($exitCode);
    }

}