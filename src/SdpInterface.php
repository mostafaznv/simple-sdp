<?php

namespace Mostafaznv\SimpleSDP;

use Illuminate\Http\Request;

interface SdpInterface
{
    public function sendMt($msisdn, array $data);

    public function charge($msisdn, array $data);

    public function sendOtp($msisdn, array $data);

    public function sendBatchMt(array $msisdn, array $data);

    public function confirmOtp($msisdn, $code, array $data);

    public function delivery(Request $request);

    public function batchDelivery(Request $request);

    public function income(Request $request);

    public function batchMo(Request $request);
}