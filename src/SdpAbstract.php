<?php

namespace Mostafaznv\SimpleSDP;

use Illuminate\Support\Facades\Validator;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Mostafaznv\SimpleSDP\Exceptions\InvalidInputException;

abstract class SdpAbstract
{
    /**
     * Driver name.
     *
     * @var string
     */
    protected $driverName;

    /**
     * Keep loaded configuration for current driver.
     *
     * @var array
     */
    protected $config;

    /**
     * Monolog instance to write all events to log file
     *
     * @var string
     */
    private $logger;

    /**
     * Attach current request to class.
     *
     * @var \Illuminate\Http\Request
     */
    public $request;

    /**
     * Enable/Disable logging.
     * @var bool
     */
    protected $logStatus = true;

    /**
     * Load ConfirmOtpLog model dynamically.
     *
     * @var \Mostafaznv\SimpleSDP\Models\ConfirmOtpLog
     */
    protected $confirmOtpLog;

    /**
     * Init configuration for current driver.
     *
     * @param $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * Set current driver name.
     *
     * @param $name
     */
    public function setDriverName($name)
    {
        $this->driverName = strtolower($name);
    }

    /**
     * Bootstrap function.
     */
    public function boot()
    {
        $this->initLog();

        $this->request = app('request');
        $this->confirmOtpLog = config('simple-sdp.models.confirm_otp_logs');
        $this->logStatus = config('simple-sdp.log');
    }

    /**
     * Init log system.
     */
    private function initLog()
    {
        $path = config('simple-sdp.log_path');
        $path = "$path/$this->driverName.log";

        $this->logger = new Logger('simple-sdp');
        $this->logger->pushHandler(new StreamHandler($path, Logger::INFO));
    }

    /**
     * Print events to log file.
     *
     * @param $message
     * @param string $level
     */
    protected function log($message, $level = 'info')
    {
        if ($this->logStatus) {
            $this->logger->{$level}($message);
        }
    }

    /**
     * Generate Unique ID.
     * @return string
     */
    protected function uniqid()
    {
        return md5(uniqid(rand(), true));
    }

    /**
     * Validate all inputs with given rules.
     *
     * @param array $input
     * @param $rules
     * @param bool $throw
     * @return array
     * @throws InvalidInputException
     */
    protected function validate(Array $input, $rules, $throw = true)
    {
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {

            if ($throw) {
                throw new InvalidInputException($validator->errors()->first());
            }
            else {
                return [
                    'status' => false,
                    'errors' => $validator->errors()->all()
                ];
            }
        }

        return [
            'status' => true,
            'errors' => []
        ];
    }

    /**
     * Patch path to baseurl to generate url.
     *
     * @param $path
     * @return string
     */
    protected function url($path)
    {
        return $this->config['baseurl'] . $path;
    }

    /**
     * Generate response.
     *
     * @param $status
     * @param $code
     * @param null $message
     * @param array $data
     * @return object
     */
    protected function response($status, $code, $message = null, $data = [])
    {
        if (is_null($message)) {
            $message = trans("simple-sdp::messages.responses.code-$code");
        }

        $response = [
            'status'  => $status,
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
        ];

        return json_decode(json_encode($response));
    }

    /**
     * Store log for success confirm opt.
     *
     * @param $msisdn
     * @param $type
     * @param null $versionName
     * @param string $ip
     * @return mixed
     */
    protected function logConfirmOtp($msisdn, $type, $versionName = null, $ip = '0:0:0:0')
    {
        $log = new $this->confirmOtpLog;
        $log->msisdn = $msisdn;
        $log->type = $type;
        $log->version_name = $versionName;
        $log->driver = $this->driverName;
        $log->ip = $ip;

        return $log->save();
    }
}