<?php

namespace Mostafaznv\SimpleSDP\Exceptions;

class DriverNotFoundException extends SimpleSDPException
{
    protected $code    = 1;
    protected $message = 'Driver is not supported.';
}