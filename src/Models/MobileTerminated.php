<?php

namespace Mostafaznv\SimpleSDP\Models;

class MobileTerminated extends Model
{
    protected $table = 'mobile_terminated';

    const DEFAULT_TYPE = 0;
    const OTP_TYPE     = 1;
    const CHARGE_TYPE  = 2;
}
