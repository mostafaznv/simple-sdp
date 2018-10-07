<?php

namespace Mostafaznv\SimpleSDP;


use Mostafaznv\SimpleSDP\AKO\AKO;
use Mostafaznv\SimpleSDP\Exceptions\DriverNotFoundException;
use Mostafaznv\SimpleSDP\SSDP\SSDP;

class SdpResolver
{
    /**
     * Keep an instance of current driver.
     *
     * @var AKO, SSDP
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
     * @return $this
     * @throws DriverNotFoundException
     */
    public function make($driver)
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
        elseif ($class = $this->validateDriver($driver)) {
            $name = $driver;
            $config = config('simple-sdp.' . strtolower($driver));

            $this->driver = new $class;
        }
        else {
            throw new DriverNotFoundException;
        }

        $this->driver->setDriverName($name);
        $this->driver->setConfig($config);
        $this->driver->boot();


        return $this;
    }
}