<?php

namespace Mostafaznv\SimpleSDP;


use Mostafaznv\SimpleSDP\AKO\AKO;
use Mostafaznv\SimpleSDP\Exceptions\DriverNotFoundException;
use Mostafaznv\SimpleSDP\FanapPlus\FanapPlus;
use Mostafaznv\SimpleSDP\Rashin\Rashin;
use Mostafaznv\SimpleSDP\SSDP\SSDP;

class SdpResolver
{
    /**
     * Keep an instance of current driver.
     *
     * @var AKO, SSDP, RASHIN, FANAPPLUS
     */
    public $driver;

    /**
     * SdpResolver constructor.
     * We resolve default sdp driver with configuration file.
     *
     * @throws DriverNotFoundException
     */
    public function __construct()
    {
        $driver = config('simple-sdp.driver');

        $this->make($driver);
    }

    public function __call($name, $arguments)
    {
        if (in_array(strtoupper($name), $this->supportedDrivers())) {
            if (isset($arguments[0])) {
                return $this->make(strtolower($name), $arguments[0]);
            }

            return $this->make(strtolower($name));
        }

        return call_user_func_array([$this->driver, $name], $arguments);
    }

    /**
     * Retrieve list of supported drivers.
     *
     * @return array
     */
    protected function supportedDrivers()
    {
        return [
            Enum::AKO,
            Enum::SSDP,
            Enum::RASHIN,
            Enum::FANAPPLUS,
        ];
    }

    /**
     * Check if driver exists.
     *
     * @param $driver
     * @return bool
     * @throws DriverNotFoundException
     */
    protected function validateDriver($driver)
    {
        $driver = strtoupper($driver);
        if (in_array($driver, $this->supportedDrivers())) {
            $const = "{$driver}_CLASS";
            $namespace = __NAMESPACE__;

            $class = constant("\\$namespace\\Enum::$const");

            return "\\$class";
        }
        else {
            throw new DriverNotFoundException;
        }
    }

    /**
     * Create an instance of SDP driver.
     *
     * @param $driver
     * @param null $customConfig
     * @return $this
     * @throws DriverNotFoundException
     */
    public function make($driver, $customConfig = null)
    {
        if ($driver InstanceOf AKO) {
            $name = Enum::AKO;
            $config = config('simple-sdp.ako');
            $this->driver = $driver;
        }
        else if ($driver InstanceOf SSDP) {
            $name = Enum::SSDP;
            $config = config('simple-sdp.ssdp');
            $this->driver = $driver;
        }
        else if ($driver InstanceOf Rashin) {
            $name = Enum::RASHIN;
            $config = config('simple-sdp.rashin');
            $this->driver = $driver;
        }
        else if ($driver InstanceOf FANAPPLUS) {
            $name = Enum::FANAPPLUS;
            $config = config('simple-sdp.fanap-plus');
            $this->driver = $driver;
        }
        elseif ($class = $this->validateDriver($driver)) {
            $name = $driver;
            $config = config('simple-sdp.' . strtolower($driver));

            $this->driver = new $class;
        }
        else {
            throw new DriverNotFoundException;
        }

        if ($customConfig) {
            $config = $customConfig;
        }

        $this->driver->setDriverName($name);
        $this->driver->setConfig($config);
        $this->driver->boot();


        return $this;
    }
}