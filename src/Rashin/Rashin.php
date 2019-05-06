<?php

namespace Mostafaznv\SimpleSDP\Rashin;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Validation\Rule;
use Mostafaznv\SimpleSDP\Enum;
use Mostafaznv\SimpleSDP\SdpAbstract;
use Mostafaznv\SimpleSDP\SdpInterface;
use Illuminate\Http\Request;
use Carbon\Carbon;

class Rashin extends SdpAbstract implements SdpInterface
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

    /**
     * Guzzle Client Instance
     *
     * @var Guzzle
     */
    protected $guzzle;

    const RESPONSE_STATUS_OK = 1;

    public function boot()
    {
        parent::boot();

        $this->mobileTerminated = config('simple-sdp.models.mobile_terminated');
        $this->mobileOriginated = config('simple-sdp.models.mobile_originated');

        $this->guzzle = new Guzzle([
            'base_uri' => $this->config['baseurl'],
            'headers'  => ['apikey' => $this->config['service_key']],
            /*'proxy'    => [
                'http'  => '127.0.0.1:1080',
                'https' => '127.0.0.1:1080',
            ]*/
        ]);
    }

    public function sendMt($msisdn, Array $data)
    {
        $this->validate($data, ['message' => 'required']);

        $this->log('start send mt to ' . $msisdn);

        $requestBody = [
            'Msisdn'  => $msisdn,
            'TraceId' => $this->uniqid(),
            'Message' => $data['message'],
        ];

        $this->log(json_encode($requestBody));

        try {
            $response = $this->guzzle->request('POST', 'Sms/Send', ['json' => $requestBody]);
            $result = json_decode($response->getBody());

            if ($response->getStatusCode() == 200) {
                if ($result->status == self::RESPONSE_STATUS_OK) {
                    $connection = isset($this->config['database']) ? $this->config['database'] : null;

                    $mt = new $this->mobileTerminated;
                    $mt->msisdn = $msisdn;
                    $mt->message = $data['message'];
                    $mt->message_id = $result->traceId;
                    $mt->type = isset($data['type']) ? $data['type'] : $this->mobileTerminated::DEFAULT_TYPE;
                    $mt->driver = $this->driverName;
                    $mt->creator_ip = $this->request->ip();

                    if ($connection) {
                        $mt->setConnection($connection);
                    }

                    $mt->save();

                    $this->log('done');

                    return $this->response(true, Enum::SUCCESS_CODE);
                }
                else {
                    $this->log('error on status code: ' . $result->status, 'error');

                    return $this->response(false, $result->status);
                }
            }
            else {
                $this->log('unexpected http status code', 'error');
            }
        }
        catch (GuzzleException $e) {
            $this->log($e->getMessage(), 'error');
        }

        return $this->response(false, Enum::UNKNOWN_ERROR_CODE);
    }

    public function sendBatchMt(array $msisdn, array $data)
    {
        return $this->response(false, Enum::NOT_IMPLEMENTED_YET);
    }

    public function charge($msisdn, Array $data)
    {
        return $this->response(false, Enum::NOT_IMPLEMENTED_YET);
    }

    public function sendOtp($msisdn, Array $data)
    {
        $this->validate($data, ['content_id' => 'required']);

        $this->log('start send otp to ' . $msisdn);

        $requestBody = [
            'Msisdn'      => $msisdn,
            'TraceId'     => $this->uniqid(),
            'ContentId'   => $data['content_id'],
            'ServiceName' => $this->config['service_name'],
            'Amount'      => $this->config['price'],
            'ChargeCode'  => $this->config['register_charge_code'],
            'Description' => isset($data['description']) ? $data['description'] : "send otp to: $msisdn",
        ];

        $this->log(json_encode($requestBody));

        try {
            $response = $this->guzzle->request('POST', 'Otp/Push', ['json' => $requestBody]);

            if ($response->getStatusCode() == 200) {
                $result = json_decode($response->getBody());

                if ($result->status == self::RESPONSE_STATUS_OK) {
                    $connection = isset($this->config['database']) ? $this->config['database'] : null;

                    $mt = new $this->mobileTerminated;
                    $mt->msisdn = $msisdn;
                    $mt->message = $data['content_id'];
                    $mt->message_id = $requestBody['TraceId'];
                    $mt->transaction_id = $result->traceId;
                    $mt->type = isset($data['type']) ? $data['type'] : $this->mobileTerminated::OTP_TYPE;
                    $mt->driver = $this->driverName;
                    $mt->creator_ip = $this->request->ip();

                    if ($connection) {
                        $mt = $mt->setConnection($connection);
                    }

                    $mt->save();

                    $this->log('done');

                    return $this->response(true, Enum::SUCCESS_CODE);
                }
                else {
                    $this->log(json_encode($result), 'error');

                    return $this->response(false, $result->status);
                }
            }
            else {
                $result = json_decode($response->getBody());

                $this->log('error: ' . json_encode($result), 'error');
            }
        }
        catch (GuzzleException $e) {
            $this->log($e->getMessage(), 'error');
        }

        return $this->response(false, Enum::UNKNOWN_ERROR_CODE);
    }

    public function confirmOtp($msisdn, $code, Array $data)
    {
        $this->log("confirm otp started for $msisdn with code $code");

        $timeout = Carbon::now()->subMinute($this->config['confirm_otp_timeout']);
        $type = isset($data['type']) ? $data['type'] : $this->mobileTerminated::OTP_TYPE;
        $connection = isset($this->config['database']) ? $this->config['database'] : null;

        $mt = $this->mobileTerminated::select('id', 'transaction_id')->where('msisdn', $msisdn)->where('type', $type)->whereDate('created_at', '<=', $timeout)->orderby('id', 'desc');

        if ($connection) {
            $this->log('connection is not empty' . $connection);
            $mt->on($connection);
        }

        $mt = $mt->first();

        if ($mt) {
            $data = [
                'TransactionPin' => $code,
                'TraceId'        => $mt->transaction_id
            ];

            try {
                $response = $this->guzzle->request('POST', 'Otp/Charge', ['json' => $data]);

                if ($response->getStatusCode() == 200) {
                    $result = json_decode($response->getBody());

                    if ($result->status == self::RESPONSE_STATUS_OK) {
                        $versionName = $this->request->version_name ? $this->request->version_name : null;
                        $ip = $this->request->ip();

                        $this->logConfirmOtp($msisdn, $type, $versionName, $ip);
                        $this->log('done');

                        return $this->response(true, Enum::SUCCESS_CODE);
                    }
                    else {
                        $this->log('unknown error. status: ' . $result->status, 'error');
                    }
                }
                else {
                    $this->log('unexpected http status code', 'error');
                }

                return $this->response(false, Enum::UNKNOWN_ERROR_CODE);
            }
            catch (GuzzleException $e) {
                $this->log('Exception', 'error');

                return $this->response(false, Enum::UNKNOWN_ERROR_CODE);
            }

        }
        else {
            $this->log('mt not found', 'error');

            return $this->response(false, Enum::MT_NOT_FOUND_CODE);
        }
    }

    public function delivery(Request $request)
    {
        return $this->response(false, Enum::NOT_IMPLEMENTED_YET);
    }

    public function batchDelivery(Request $request)
    {
        return $this->response(false, Enum::NOT_IMPLEMENTED_YET);
    }

    public function income(Request $request)
    {
        $this->log('income message received');

        $validator = $this->validate($request->all(), [
            'Msisdn'    => ['required', 'digits:12', 'regex:/^989[0-9]{9}$/'],
            'ShortCode' => [
                'required',
                Rule::in([$this->config['short_code']])
            ],
            'Message'   => 'required|string',
        ], false);

        if ($validator['status']) {
            return $this->handleIncome($request->Msisdn, $request->Message, $request->ip());
        }
        else {
            $this->log('not valid input ' . json_encode($validator['errors']), 'error');

            return $this->response(false, Enum::NOT_VALID_INPUTS_CODE);
        }
    }

    protected function handleIncome($msisdn, $message, $ip = '0:0:0:0')
    {
        $mg = new $this->mobileOriginated;
        $mg->msisdn = $msisdn;
        $mg->message = $message;
        $mg->transaction_id = '-';
        $mg->received_at = Carbon::now();
        $mg->driver = $this->driverName;
        $mg->creator_ip = $ip;

        if ($mg->save()) {
            switch (strtolower(trim($message))) {
                case '+':
                    $this->log('empty message');

                    $this->sendMt($msisdn, [
                        'content_id' => 0,
                        'message'    => trans("simple-sdp::messages.{$this->config['trans_prefix']}.inform")
                    ]);

                    break;

                default:
                    $this->log('simple-sdp called mt (guide)');

                    $this->sendMt($msisdn, [
                        'content_id' => 0,
                        'message'    => trans("simple-sdp::messages.{$this->config['trans_prefix']}.guide")
                    ]);
                    break;

            }

            $this->log('done');

            return $this->response(true, Enum::SUCCESS_CODE);
        }
        else {
            $this->log('unknown error', 'error');

            return $this->response(false, Enum::UNKNOWN_ERROR_CODE);
        }
    }

    public function batchMo(Request $request)
    {
        return $this->response(false, Enum::NOT_IMPLEMENTED_YET);
    }

    /**
     * Generate Unique ID.
     * @return string
     */
    protected function uniqid()
    {
        return time();
    }
}