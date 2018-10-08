<?php

namespace Mostafaznv\SimpleSDP\AKO;

use GuzzleHttp\Client as Guzzle;
use Illuminate\Validation\Rule;
use Mostafaznv\SimpleSDP\Enum;
use Mostafaznv\SimpleSDP\SdpAbstract;
use Mostafaznv\SimpleSDP\SdpInterface;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AKO extends SdpAbstract implements SdpInterface
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
        $this->validate($data, ['content_id' => 'required', 'message' => 'required', 'is_free' => 'nullable|boolean']);

        $this->log('start send mt to ' . $msisdn);

        $url = $this->url('sendmessage/');
        $description = strtr($this->config['description'], [
            ':short_code' => $this->config['short_code'],
            ':content_id' => $data['content_id']
        ]);

        $requestBody = [
            'username'    => $this->config['username'],
            'password'    => $this->config['password'],
            'serviceid'   => $this->config['service_id'],
            'shortcode'   => $this->config['short_code'],
            'msisdn'      => $msisdn,
            'description' => $description,
            'chargecode'  => $this->config['sub_charging_code'],
            'amount'      => $this->config['amount'],
            'currency'    => $this->config['currency'],
            'message'     => $data['message'],
            'is_free'     => isset($data['is_free']) ? $data['is_free'] : $this->config['is_free'],
            'correlator'  => $this->config['short_code'] . '-' . $this->uniqid(),
            'servicename' => $this->config['service_name'],
        ];

        $this->log(json_encode($requestBody));

        $client = new Guzzle();
        $response = $client->request('POST', $url, ['form_params' => $requestBody]);
        $result = json_decode($response->getBody());

        if ($response->getStatusCode() == 200) {
            if ($result->status_code == 0) {
                $mt = new $this->mobileTerminated;
                $mt->msisdn = $msisdn;
                $mt->message = $data['message'];
                $mt->message_id = $requestBody['correlator'];
                $mt->type = $this->mobileTerminated::DEFAULT_TYPE;
                $mt->driver = $this->driverName;
                $mt->creator_ip = $this->request->ip();
                $mt->save();

                $this->log('done');

                return $this->response(true, Enum::SUCCESS_CODE);
            }
        }

        $this->log(json_encode($result), 'error');

        return $this->response(false, Enum::UNKNOWN_ERROR_CODE);
    }

    public function charge($msisdn, Array $data)
    {
        $this->validate($data, ['content_id' => 'required']);

        $this->log('start charge to ' . $msisdn);

        $url = $this->url('charging/');
        $description = strtr($this->config['description'], [
            ':short_code' => $this->config['short_code'],
            ':content_id' => $data['content_id']
        ]);


        $requestBody = [
            'username'    => $this->config['username'],
            'password'    => $this->config['password'],
            'serviceid'   => $this->config['service_id'],
            'msisdn'      => $msisdn,
            'description' => $description,
            'chargecode'  => $this->config['charging_code'],
            'amount'      => $this->config['amount'],
            'currency'    => $this->config['currency'],
            'correlator'  => $this->config['charging_code'] . '-' . $this->uniqid(),
        ];

        $this->log(json_encode($requestBody));

        $client = new Guzzle();
        $response = $client->request('POST', $url, ['form_params' => $requestBody]);
        $result = json_decode($response->getBody());

        if ($response->getStatusCode() == 200) {
            if ($result->status_code == 0) {
                $mt = new $this->mobileTerminated;
                $mt->msisdn = $msisdn;
                $mt->message = '';
                $mt->message_id = $requestBody['correlator'];
                $mt->type = $this->mobileTerminated::CHARGE_TYPE;
                $mt->driver = $this->driverName;
                $mt->creator_ip = $this->request->ip();
                $mt->save();

                $this->log('done');

                return $this->response(true, Enum::SUCCESS_CODE);
            }
        }

        $this->log(json_encode($result), 'error');

        return $this->response(false, Enum::UNKNOWN_ERROR_CODE);
    }

    public function sendOtp($msisdn, Array $data)
    {
        $this->validate($data, ['content_id' => 'required']);

        $this->log('start send otp to ' . $msisdn);

        $url = $this->url('otp-generation/');
        $requestBody = [
            'msisdn'        => $msisdn,
            'username'      => $this->config['username'],
            'password'      => $this->config['password'],
            'servicename'   => $this->config['service_name'],
            'serviceid'     => $this->config['service_id'],
            'referencecode' => $this->config['short_code'] . '-' . $this->uniqid(),
            'shortcode'     => $this->config['short_code'],
            'contentid'     => $data['content_id'],
            'chargecode'    => $this->config['sub_charging_code'],
            'description'   => 'OTP',
            'amount'        => config('settings.amount'),
        ];

        $this->log(json_encode($requestBody));

        $client = new Guzzle();
        $response = $client->request('POST', $url, ['form_params' => $requestBody]);

        if ($response->getStatusCode() == 200) {
            $result = json_decode($response->getBody());

            $statusCode = $result->data->statusInfo->statusCode;
            if ($statusCode == 200) {
                $mt = new $this->mobileTerminated;
                $mt->msisdn = $msisdn;
                $mt->message = $data['content_id'];
                $mt->message_id = $requestBody['referencecode'];
                $mt->transaction_id = $result->data->statusInfo->OTPTransactionId;
                $mt->type = $this->mobileTerminated::OTP_TYPE;
                $mt->driver = $this->driverName;
                $mt->creator_ip = $this->request->ip();
                $mt->save();

                $this->log('done');

                return $this->response(true, Enum::SUCCESS_CODE);
            }
            elseif ($statusCode == 500) {
                $error = $result->data->statusInfo->errorInfo->errorCode;
                $code = Enum::UNKNOWN_ERROR_CODE;

                if ($error == 51035) {
                    $code = Enum::ALREADY_EXISTS_CODE;
                    $this->log('already exists', 'error');
                }
                else {
                    $this->log('unknown error', 'error');
                }

                return $this->response(false, $code);
            }
        }

        return $this->response(false, Enum::UNKNOWN_ERROR_CODE);
    }

    public function confirmOtp($msisdn, $code, Array $data)
    {
        $this->validate($data, ['content_id' => 'required']);

        $this->log("confirm otp started for $msisdn with code $code");

        $timeout = Carbon::now()->subMinute($this->config['confirm_otp_timeout']);
        $mt = $this->mobileTerminated::where('msisdn', $msisdn)->where('type', $this->mobileTerminated::OTP_TYPE)->whereDate('created_at', '<=', $timeout)->orderby('id', 'desc')->first();

        if ($mt) {
            $url = $this->url('otp-confirmation/');
            $data = [
                'msisdn'         => $msisdn,
                'username'       => $this->config['username'],
                'password'       => $this->config['password'],
                'serviceid'      => $this->config['service_id'],
                'referencecode'  => $mt->message_id,
                'shortcode'      => $this->config['short_code'],
                'contentid'      => $data['content_id'],
                'message'        => $code,
                'otptransaction' => $mt->transaction_id,
            ];

            $client = new Guzzle();

            $response = $client->request('POST', $url, ['form_params' => $data]);
            if ($response->getStatusCode() == 200) {
                $result = json_decode($response->getBody());

                $status_code = $result->data->statusInfo->statusCode;

                if ($status_code == 200) {
                    $versionName = $this->request->version_name ? $this->request->version_name : null;
                    $ip = $this->request->ip();

                    $this->logConfirmOtp($msisdn, $this->confirmOtpLog::OTP_APP_TYPE, $versionName, $ip);

                    $this->log('done');
                    return $this->response(true, Enum::SUCCESS_CODE);
                }
            }

            $this->log('unknown error', 'error');
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
            'msisdn'         => ['required', 'digits:12', 'regex:/^989[0-9]{9}$/'],
            'correlator'     => 'required',
            'deliverystatus' => 'required'
        ], false);

        if ($validator['status']) {
            $this->log('for ' . $request->msisdn);
            $mt = $this->mobileTerminated::where('msisdn', $request->msisdn)->where('message_id', $request->correlator)->first();

            if ($mt) {
                $mt->delivery_status = $request->deliverystatus;
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
        $this->log('batch delivery called');

        $xml = $request->getContent();

        $cleanXml = str_ireplace(['soapenv:', 'loc:'], '', $xml);
        $xml = simplexml_load_string($cleanXml);
        $collection = (array)$xml->Body->notifySmsDeliveryReceipt->notifySmsDeliveryReceiptCollection;

        $result = [];
        foreach ((array)$collection['NotifySmsDeliveryReceipt'] as $item) {
            $item = (array)$item;
            $itemDeliveryStatus = (array)$item['deliveryStatus'];
            $address = str_replace('tel:', '', $itemDeliveryStatus['address']);

            $result[] = $this->handleBatchDelivery((string)$address, (string)$item["correlator"], (string)$itemDeliveryStatus['deliveryStatus'], $request->ip());
        }

        if (!in_array(false, $result)) {
            $this->log('done');
            $this->log(json_encode($result));

            return $this->response(true, Enum::SUCCESS_CODE, null, $result);
        }
        else {
            $this->log('failed', 'error');
            $this->log(json_encode($result), 'error');

            return $this->response(false, Enum::UNKNOWN_ERROR_CODE, null, $result);
        }
    }

    private function handleBatchDelivery($msisdn, $correlator, $deliveryStatus, $ip = '0:0:0:0')
    {
        $mt = $this->mobileTerminated::where('msisdn', $msisdn)->where('message_id', $correlator)->first();
        if ($mt) {
            $mt->delivery_status = $deliveryStatus;
            $mt->updater_ip = $ip;
            if ($mt->save()) {
                return true;
            }
        }

        return false;
    }

    public function income(Request $request)
    {
        $this->log('income message received');
        $validator = $this->validate($request->all(), [
            'msisdn'      => ['required', 'digits:12', 'regex:/^989[0-9]{9}$/'],
            'shortcode'   => [
                'required',
                'numeric',
                Rule::in([$this->config['short_code']])
            ],
            'message'     => 'required|string',
            'partnername' => 'string',
            'trans_id'    => 'string',
        ], false);

        if ($validator['status']) {
            return $this->handleIncome($request->msisdn, $request->shortcode, $request->trans_id, $request->message, $request->ip());
        }
        else {
            $this->log('not valid input ' . json_encode($validator['errors']), 'error');
            return $this->response(false, Enum::NOT_VALID_INPUTS_CODE);
        }
    }

    private function handleIncome($msisdn, $shortcode, $transactionId, $message, $ip = '0:0:0:0')
    {
        if ($shortcode == $this->config['short_code']) {
            $mg = new $this->mobileOriginated;
            $mg->msisdn = $msisdn;
            $mg->message = $message;
            $mg->transaction_id = $transactionId;
            $mg->received_at = Carbon::now();
            $mg->driver = $this->driverName;
            $mg->creator_ip = $ip;

            if ($mg->save()) {
                switch (strtolower(trim($message))) {
                    case '+':
                        $this->log('empty message');
                        $this->log('simple-sdp called mt (inform)');

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
        else {
            $this->log('not valid input (shortcode)', 'error');
            return $this->response(false, Enum::NOT_VALID_INPUTS_CODE);
        }
    }

    public function batchMo(Request $request)
    {
        $this->log('batch mo received');

        $xml = $request->getContent();

        $cleanXml = str_ireplace(['soapenv:', 'loc:'], '', $xml);
        $xml = simplexml_load_string($cleanXml);
        $collection = $xml->Body->sendSms->sendSmsCollection->SendSms;

        $status = 1;
        $result = [];

        foreach ($collection as $item) {
            $msisdn = (string)$item->addresses;
            $shortcode = (string)$item->senderName;
            $transactionId = (string)$item->correlator;
            $message = (string)$item->message;

            $response = $this->handleIncome($msisdn, $shortcode, $transactionId, $message, $request->ip());
            $result[] = $response;

            $status = $status * (int)$response->status;

        }

        if ($status) {
            $this->log('done');
            $this->log(json_encode($result));

            return $this->response(true, Enum::SUCCESS_CODE, null, $result);
        }
        else {
            $this->log('failed', 'error');
            $this->log(json_encode($result), 'error');

            return $this->response(false, Enum::UNKNOWN_ERROR_CODE, null, $result);
        }
    }
}