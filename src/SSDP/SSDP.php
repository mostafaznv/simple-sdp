<?php

namespace Mostafaznv\SimpleSDP\SSDP;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Validation\Rule;
use Mostafaznv\SimpleSDP\Enum;
use Mostafaznv\SimpleSDP\SdpAbstract;
use Mostafaznv\SimpleSDP\SdpInterface;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SSDP extends SdpAbstract implements SdpInterface
{
    /**
     * Load MobileTerminated model dynamically.
     *
     * @var \Mostafaznv\SimpleSDP\Models\MobileTerminated
     */
    protected $mobileTerminated;

    /**
     * Load MobileOriginated model dynamically.
     *
     * @var \Mostafaznv\SimpleSDP\Models\MobileOriginated
     */
    protected $mobileOriginated;

    public function boot()
    {
        parent::boot();

        $this->mobileTerminated = config('simple-sdp.models.mobile_terminated');
        $this->mobileOriginated = config('simple-sdp.models.mobile_originated');
    }

    public function sendMt($msisdn, Array $data)
    {
        $this->validate($data, ['message' => 'required']);

        $this->log('start send mt to ' . $msisdn);

        $url = $this->url('transfer/send');
        $requestBody = [
            'to'        => $msisdn,
            'from'      => $this->config['short_code'],
            'serviceId' => $this->config['service_id'],
            'username'  => $this->config['username'],
            'password'  => $this->config['password'],
            'sc'        => $this->config['short_code'],
            'message'   => $data['message'],
            'messageId' => $this->config['short_code'] . '-' . $this->uniqid(),
        ];

        $this->log(json_encode($requestBody));

        $client = new Guzzle();

        try {
            $response = $client->request('GET', $url, ['query' => $requestBody]);
            $result = simplexml_load_string($response->getBody());

            if ($response->getStatusCode() == 200) {
                if ($result->status == 0) {
                    $mt = new $this->mobileTerminated;
                    $mt->msisdn = $msisdn;
                    $mt->message = $requestBody['message'];
                    $mt->message_id = $requestBody['messageId'];
                    $mt->transaction_id = $requestBody['messageId'];
                    $mt->creator_ip = $this->request->ip();
                    $mt->save();

                    $this->log('done');

                    return $this->response(true, Enum::SUCCESS_CODE);
                }
            }
        }
        catch (GuzzleException $e) {
            $this->log($e->getMessage(), 'error');
            return $this->response(false, Enum::UNKNOWN_ERROR_CODE, $e->getMessage());
        }

        $this->log(json_encode($result), 'error');
        return $this->response(false, Enum::UNKNOWN_ERROR_CODE);
    }

    public function charge($msisdn, Array $data)
    {
        $this->validate($data, ['content_id' => 'required']);

        $this->log('start charge to ' . $msisdn);

        $url = $this->url('transfer/charge');
        $requestBody = [
            'to'           => $msisdn,
            'from'         => $this->config['short_code'],
            'serviceId'    => $this->config['service_id'],
            'username'     => $this->config['username'],
            'password'     => $this->config['password'],
            'sc'           => $this->config['short_code'],
            'chargingCode' => $this->config['charging_code'],
            'message'      => $this->config['message'],
            'messageId'    => $this->config['short_code'] . '-' . $this->uniqid(),
            'contentId'    => $data['content_id'],
        ];

        $this->log(json_encode($requestBody));


        $client = new Guzzle();

        try {
            $response = $client->request('GET', $url, ['query' => $requestBody]);
            $result = simplexml_load_string($response->getBody());

            if ($response->getStatusCode() == 200) {
                if ($result->status == 0) {
                    $mt = new $this->mobileTerminated;
                    $mt->msisdn = $msisdn;
                    $mt->message = $requestBody['message'];
                    $mt->message_id = $requestBody['messageId'];
                    $mt->type = $this->mobileTerminated::CHARGE_TYPE;
                    $mt->creator_ip = $this->request->ip();
                    $mt->save();

                    $this->log('done');

                    return $this->response(true, Enum::SUCCESS_CODE);
                }
            }
        }
        catch (GuzzleException $e) {
            $this->log($e->getMessage(), 'error');
            return $this->response(false, Enum::UNKNOWN_ERROR_CODE, $e->getMessage());
        }

        $this->log(json_encode($result), 'error');
        return $this->response(false, Enum::UNKNOWN_ERROR_CODE);
    }

    public function sendOtp($msisdn, Array $data)
    {
        $this->validate($data, ['content_id' => 'required']);

        $this->log('start send otp to ' . $msisdn);

        $url = $this->url('pin/generate');
        $requestBody = [
            'to'           => $msisdn,
            'from'         => $this->config['short_code'],
            'serviceId'    => $this->config['service_id'],
            'username'     => $this->config['username'],
            'password'     => $this->config['password'],
            'message'      => $this->config['message'],
            'messageId'    => $this->config['short_code'] . '-' . $this->uniqid(),
            'contentId'    => $data['content_id'],
            'sc'           => $this->config['short_code'],
            'chargingCode' => $this->config['sub_charging_code'],
        ];

        $this->log(json_encode($requestBody));

        $client = new Guzzle();

        try {
            $response = $client->request('GET', $url, ['query' => $requestBody]);
            $result = json_decode($response->getBody());

            if ($response->getStatusCode() == 200) {
                if ($result->status == 0) {
                    $mt = new $this->mobileTerminated;
                    $mt->msisdn = $msisdn;
                    $mt->message = $requestBody['contentId'];
                    $mt->message_id = $requestBody['messageId'];
                    $mt->transaction_id = $result->transactionId;
                    $mt->type = $this->mobileTerminated::OTP_TYPE;
                    $mt->creator_ip = $this->request->ip();
                    $mt->save();

                    $this->log('done');

                    return $this->response(true, Enum::SUCCESS_CODE);
                }
                else if ($result->status == 500 and $result->message == Enum::SSDP_OTP_EXISTS) {
                    $this->log('already exists', 'error');
                    return $this->response(false, Enum::ALREADY_EXISTS_CODE);
                }
            }
        }
        catch (GuzzleException $e) {
            $this->log($e->getMessage(), 'error');
            return $this->response(false, Enum::UNKNOWN_ERROR_CODE, $e->getMessage());
        }

        $this->log('unknown error', 'error');
        return $this->response(false, Enum::UNKNOWN_ERROR_CODE);

    }

    public function confirmOtp($msisdn, $code, Array $data)
    {
        $this->validate($data, ['content_id' => 'required']);

        $this->log("confirm otp started for $msisdn with code $code");

        $timeout = Carbon::now()->subMinute($this->config['confirm_otp_timeout']);
        $mt = $this->mobileTerminated::where('msisdn', $msisdn)->where('type', $this->mobileTerminated::OTP_TYPE)->whereDate('created_at', '<=', $timeout)->orderby('id', 'desc')->first();


        if ($mt) {
            $url = $this->url('pin/confirm');
            $requestBody = [
                'to'            => $msisdn,
                'pin'           => $code,
                'sc'            => $this->config['short_code'],
                'serviceId'     => $this->config['service_id'],
                'from'          => $this->config['short_code'],
                'password'      => $this->config['password'],
                'username'      => $this->config['username'],
                'messageId'     => $mt->message_id,
                'transactionId' => $mt->transaction_id,

            ];

            $this->log(json_encode($requestBody));

            $client = new Guzzle();

            try {
                $response = $client->request('GET', $url, ['query' => $requestBody]);
                $result = json_decode($response->getBody());

                if ($response->getStatusCode() == 200) {
                    if ($result->status == 0) {
                        $versionName = $this->request->version_name ? $this->request->version_name : null;
                        $ip = $this->request->ip();

                        $this->confirmOtpLog($msisdn, $this->confirmOtpLog::OTP_APP_TYPE, $versionName, $ip);

                        $this->log('done');
                        return $this->response(true, Enum::SUCCESS_CODE);
                    }
                }
            }
            catch (GuzzleException $e) {
                $this->log($e->getMessage(), 'error');
                return $this->response(false, Enum::UNKNOWN_ERROR_CODE, $e->getMessage());
            }

            $this->log(json_encode($result), 'error');
            return $this->response(false, Enum::UNKNOWN_ERROR_CODE);
        }
        else {
            $this->log('mt not found', 'error');
            return $this->response(false, Enum::MT_NOT_FOUND_CODE);
        }
    }

    public function delivery(Request $request)
    {
        $this->log('delivery received');

        $validator = $this->validate($request->all(), [
            'refId'          => 'required',
            'deliveryStatus' => 'required',
        ], false);

        if ($validator['status']) {
            $this->log('for ' . $request->msisdn);
            $mt = $this->mobileTerminated::where('message_id', $request->refId)->first();

            if ($mt) {
                $mt->delivery_status = $request->deliveryStatus;
                $mt->updater_ip = $request->ip();
                $mt->save();

                $this->log('done');

                return $this->response(true, Enum::SUCCESS_CODE);
            }

            $this->log('unknown error', 'error');
            return $this->response(false, Enum::UNKNOWN_ERROR_CODE);
        }
        else {
            $this->log('not valid input ' . json_encode($validator['errors']), 'error');
            return $this->response(false, Enum::NOT_VALID_INPUTS_CODE);
        }
    }

    public function batchDelivery(Request $request)
    {
        return $this->response(false, Enum::NOT_IMPLEMENTED_YET);
    }

    public function income(Request $request)
    {
        $this->log('income message received');
        $validator = $this->validate($request->all(), [
            'da' => 'required',
            'oa' => ['required', Rule::in([$this->config['short_code']])],
            'sc' => ['required', Rule::in([$this->config['username']])],
        ], false);

        if ($validator['status']) {
            $message = $request->txt ? $request->txt : '';

            $mg = new $this->mobileOriginated;
            $mg->msisdn = $request->da;
            $mg->message = $message;
            $mg->transaction_id = $request->ts ? $request->ts : '--';
            $mg->received_at = Carbon::now();
            $mg->creator_ip = $request->ip();
            $mg->save();

            switch (strtolower(trim($message))) {
                case '':
                    $this->log('empty message');
                    $this->log('simple-sdp called mt (inform)');

                    $this->sendMt($request->da, [
                        'content_id' => 0,
                        'message'    => trans("simple-sdp::messages.mt.inform")
                    ]);

                    break;

                default:
                    $this->log('simple-sdp called mt (guide)');

                    $this->sendMt($request->da, [
                        'content_id' => 0,
                        'message'    => trans("simple-sdp::messages.mt.guide")
                    ]);

                    break;

            }

            $this->log('done');
            return $this->response(true, Enum::SUCCESS_CODE);
        }
        else {
            $this->log('not valid input ' . json_encode($validator['errors']), 'error');
            return $this->response(false, Enum::NOT_VALID_INPUTS_CODE);
        }
    }

    public function batchMo(Request $request)
    {
        return $this->response(false, Enum::NOT_IMPLEMENTED_YET);
    }
}