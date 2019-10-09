<?php

namespace Mostafaznv\SimpleSDP;

use Mostafaznv\SimpleSDP\AKO\AKO;
use Mostafaznv\SimpleSDP\FanapPlus\FanapPlus;
use Mostafaznv\SimpleSDP\Rashin\Rashin;
use Mostafaznv\SimpleSDP\SSDP\SSDP;

class Enum
{
    const AKO       = 'AKO';
    const AKO_CLASS = AKO::class;

    const RASHIN       = 'RASHIN';
    const RASHIN_CLASS = Rashin::class;

    const FANAPPLUS       = 'FANAPPLUS';
    const FANAPPLUS_CLASS = FanapPlus::class;


    const SSDP            = 'SSDP';
    const SSDP_CLASS      = SSDP::class;
    const SSDP_OTP_EXISTS = 'PARTNER API GATEWAY RECORD ALREADY EXIST.';

    const SUB_STATUS          = 0;
    const UNSUB_CHARGE_STATUS = 5;

    const APP_CHANNEL       = 'APP';
    const WAP_CHANNEL       = 'WAP';
    const SMS_CHANNEL       = 'SMS';
    const CRM_CHANNEL       = 'CRM';
    const OTP_CHANNEL       = 'OTP';
    const TAJMI_CHANNEL     = 'TAJMI';
    const CP_CHANNEL        = 'CP';
    const USSD_CHANNEL      = 'USSD';
    const OPERATOR_CHANNEL  = 'OPERATOR';
    const HAMRAHMAN_CHANNEL = 'HAMRAHMAN';

    const SUB_EVENT_TYPE    = 1.1;
    const UNSUB_EVENT_TYPE  = 1.2;
    const CHARGE_EVENT_TYPE = 1.5;

    const ALREADY_EXISTS_CODE   = 1;
    const MT_NOT_FOUND_CODE     = 2;
    const NOT_VALID_INPUTS_CODE = 3;
    const NOT_IMPLEMENTED_YET   = 4;
    const SUCCESS_CODE          = 200;
    const UNKNOWN_ERROR_CODE    = 400;
}