<?php

namespace Mostafaznv\SimpleSDP;

use Illuminate\Http\Request;

interface SdpInterface
{
    public function sendMt($msisdn, Array $data);

    public function charge($msisdn, Array $data);

    public function sendOtp($msisdn, Array $data);

    public function confirmOtp($msisdn, $code, Array $data);

    public function delivery(Request $request);

    public function batchDelivery(Request $request);

    public function income(Request $request);

    public function batchMo(Request $request);
}