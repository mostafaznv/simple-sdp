<?php

namespace Mostafaznv\SimpleSDP\Exceptions;

class InvalidInputException extends SimpleSDPException
{
    protected $code    = 2;
    protected $message = 'Input is invalid';
}