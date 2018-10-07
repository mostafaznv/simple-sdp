<?php

namespace Mostafaznv\SimpleSDP;

use Mostafaznv\SimpleSDP\AKO\AKO;
use Mostafaznv\SimpleSDP\SSDP\SSDP;

class Enum
{
    const AKO       = 'AKO';
    const AKO_CLASS = AKO::class;

    const SSDP            = 'SSDP';
    const SSDP_CLASS      = SSDP::class;
    const SSDP_OTP_EXISTS = 'PARTNER API GATEWAY RECORD ALREADY EXIST.';


    const ALREADY_EXISTS_CODE   = 1;
    const MT_NOT_FOUND_CODE     = 2;
    const NOT_VALID_INPUTS_CODE = 3;
    const NOT_IMPLEMENTED_YET   = 4;
    const SUCCESS_CODE          = 200;
    const UNKNOWN_ERROR_CODE    = 400;
}