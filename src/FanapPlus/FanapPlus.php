<?php

namespace Mostafaznv\SimpleSDP\FanapPlus;

use Exception;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Validation\Rule;
use Mostafaznv\SimpleSDP\Enum;
use Mostafaznv\SimpleSDP\Models\MobileOriginated;
use Mostafaznv\SimpleSDP\Models\MobileTerminated;
use Mostafaznv\SimpleSDP\SdpAbstract;
use Mostafaznv\SimpleSDP\SdpInterface;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Mostafaznv\SimpleSDP\Traits\RSA;
use Mostafaznv\SimpleSDP\Exceptions\InvalidInputException;

class FanapPlus extends SdpAbstract implements SdpInterface
{
    use RSA;

    /**
     * Load MobileTerminated model dynamically.
     *
     * @var MobileTerminated
     */
    protected $mobileTerminated;

    /**
     * Load MobileOriginated model dynamically.
     *
     * @var MobileOriginated
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
            'headers'  => [
                'Content-Type' => 'application/json;charset=utf-8'
            ],
        ]);
    }

    /**
     * Send MT
     * @param $msisdn
     * @param array $data
     * @return object
     * @throws InvalidInputException
     */
    public function sendMt($msisdn, Array $data)
    {
        $this->validate($data, ['message' => 'required']);

        $this->log('start send mt to ' . $msisdn);

        $requestBody = $this->mtMessageRequestBody($msisdn, $data['message']);

        try {
            $response = $this->guzzle->request('POST', 'message/post', ['body' => json_encode($requestBody)]);

            if ($response->getStatusCode() == 200) {
                $result = json_decode($response->getBody());

                if ($result->Puid and isset($result->Muids[0])) {
                    $connection = isset($this->config['database']) ? $this->config['database'] : null;

                    $mt = new $this->mobileTerminated;
                    $mt->msisdn = $msisdn;
                    $mt->message = $data['message'];
                    $mt->message_id = $result->Muids[0];
                    $mt->transaction_id = $result->Puid;
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
                    $this->log('error, MUID or PUID is not set', 'error');

                    return $this->response(false, Enum::UNKNOWN_ERROR_CODE, 'error, PUID is not set');
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

    // TODO
    public function sendBatchMt(array $msisdn, array $data)
    {
        return $this->response(false, Enum::NOT_IMPLEMENTED_YET);
    }

    public function charge($msisdn, Array $data)
    {
        return $this->response(false, Enum::NOT_IMPLEMENTED_YET);
    }

    /**
     * Send OTP
     *
     * @param $msisdn
     * @param array $data
     * @return object
     * @throws Exception
     */
    public function sendOtp($msisdn, Array $data = [])
    {
        $this->log('start send otp to ' . $msisdn);

        $requestBody = $this->otpMessageRequestBody($msisdn);

        try {
            $response = $this->guzzle->request('POST', 'message/post', ['body' => json_encode($requestBody)]);

            if ($response->getStatusCode() == 200) {
                $result = json_decode($response->getBody());

                if ($result->Puid and isset($result->Muids[0]) and isset($result->SyncDeliveries[0]->DeliveryStatus) and $result->SyncDeliveries[0]->DeliveryStatus == 'OperationCompleted') {
                    $connection = isset($this->config['database']) ? $this->config['database'] : null;

                    $mt = new $this->mobileTerminated;
                    $mt->msisdn = $msisdn;
                    $mt->message = $requestBody['Messages']['Content'];
                    $mt->message_id = $result->Muids[0];
                    $mt->transaction_id = $result->Puid;
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

                    if (isset($result->SyncDeliveries[0]->Result)) {
                        return $this->response(false, Enum::UNKNOWN_ERROR_CODE, $result->SyncDeliveries[0]->Result);
                    }

                    return $this->response(false, Enum::UNKNOWN_ERROR_CODE);
                }
            }
            else {
                $this->log('error, MUID or PUID is not set', 'error');

                return $this->response(false, Enum::UNKNOWN_ERROR_CODE, 'error, PUID is not set');
            }
        }
        catch (GuzzleException $e) {
            $this->log($e->getMessage(), 'error');
        }

        return $this->response(false, Enum::UNKNOWN_ERROR_CODE);
    }

    /**
     * Confirm OTP
     *
     * @param $msisdn
     * @param $code
     * @param array $data
     * @return object
     * @throws Exception
     */
    public function confirmOtp($msisdn, $code, Array $data = [])
    {
        $this->log("confirm otp started for $msisdn with code $code");

        $timeout = Carbon::now()->subMinute($this->config['confirm-otp-timeout']);
        $type = isset($data['type']) ? $data['type'] : $this->mobileTerminated::OTP_TYPE;
        $connection = isset($this->config['database']) ? $this->config['database'] : null;

        $mt = $this->mobileTerminated::select('id', 'transaction_id')->where('msisdn', $msisdn)->where('type', $type)->whereDate('created_at', '<=', $timeout)->orderby('id', 'desc');

        if ($connection) {
            $this->log('connection is: ' . $connection);
            $mt->on($connection);
        }

        $mt = $mt->first();

        if ($mt) {
            $requestBody = $this->confirmOtpMessageRequestBody($msisdn, $code);

            try {
                $response = $this->guzzle->request('POST', 'message/post', ['body' => json_encode($requestBody)]);

                if ($response->getStatusCode() == 200) {
                    $result = json_decode($response->getBody());

                    $generalConditions = $result->Puid and isset($result->Muids[0]);
                    $signupConditions = (isset($result->SyncDeliveries[0]->DeliveryStatus) and $result->SyncDeliveries[0]->DeliveryStatus == 'OperationCompleted');

                    if ($this->config['login-mode']) {
                        $loginConditions = (isset($result->SyncDeliveryStatuses[0]) and $result->SyncDeliveryStatuses[0] == 'OperationFailed-Request already exists');

                        $this->log('login mode');
                    }
                    else {
                        $this->log('signup mode');
                        $loginConditions = false;
                    }

                    if ($generalConditions and ($signupConditions or $loginConditions)) {
                        $versionName = $this->request->version_name ? $this->request->version_name : null;
                        $ip = $this->request->ip();

                        $this->logConfirmOtp($msisdn, $type, $versionName, $ip);
                        $this->log('done');

                        return $this->response(true, Enum::SUCCESS_CODE);
                    }
                    else {
                        if (isset($result->SyncDeliveries[0]->Result)) {
                            $this->log('unknown error. status: ' . $result->SyncDeliveries[0]->Result, 'error');

                            return $this->response(false, Enum::UNKNOWN_ERROR_CODE, $result->SyncDeliveries[0]->Result);
                        }
                        else {
                            $this->log('unknown error.', 'error');
                        }
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

    /**
     * Receive income messages
     *
     * @param Request $request
     * @return object
     * @throws InvalidInputException
     */
    public function income(Request $request)
    {
        $this->log('income message received');

        $validator = $this->validate($request->input(0), [
            'Muid'            => 'required',
            'Sid'             => 'required',
            'AppId'           => 'required',
            'ReceiveTime'     => 'required',
            'ChannelType'     => 'required',
            'Channel'         => 'required',
            'AccountId'       => 'required',
            'UserPhoneNumber' => 'required',
            'MessageType'     => 'required|in:Subscription,Unsubscription,Content,PremiumContent,SubscriptionQueryResult',
            'Content'         => 'nullable',
            'Actor'           => 'required|in:Sms,Cp,Tajmi,Ussd,Operator,Hamrahman',
            'Signature'       => 'required',
        ], false);

        if ($validator['status']) {
            if ($request->input('0.MessageType') == 'Content') {
                return $this->handleIncome($request->input('0.UserPhoneNumber'), $request->input('0.Content'), $request->ip());
            }
            else {
                $this->log("request is not an income message ({$request->input('0.MessageType')})", 'error');

                return $this->response(false, Enum::NOT_VALID_INPUTS_CODE);
            }

        }
        else {
            $this->log('not valid input ' . json_encode($validator['errors']), 'error');

            return $this->response(false, Enum::NOT_VALID_INPUTS_CODE);
        }
    }

    /**
     * Handle income messages and Reply them
     * @param $msisdn
     * @param $message
     * @param string $ip
     * @return object
     * @throws InvalidInputException
     */
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
     * Generate Sign Data
     *
     * @param $message
     * @return string
     */
    protected function signData($message)
    {
        return "{$message['Date']},{$message['Uid']},{$message['Messages']['Sid']},{$message['Messages']['ChannelType']},{$message['Messages']['MessageType']},{$message['Messages']['UserPhoneNumber']},{$message['Messages']['Content']}";
    }

    /**
     * Produce General Message Body
     *
     * @param $phone
     * @return array
     */
    protected function messageRequestBody($phone)
    {
        return [
            'Uid'                 => $this->config['uid'],
            'AppId'               => $this->config['app-id'],
            'Date'                => $this->dateTime(),
            'SyncDeliveryTimeout' => '5000',
            'Messages'            => [
                'Sid'             => $this->config['service-id'],
                'UserPhoneNumber' => $phone,
                'Content'         => '',
                'MessageType'     => '',
                'ChannelType'     => $this->config['channel-type'],
                'Priority'        => $this->config['priority'],
                'ExpirationTime'  => $this->dateTime($this->config['confirm-otp-timeout']),
                'Signature'       => '',
            ]
        ];
    }

    /**
     * Produce Message Body to Send MT
     *
     * @param $phone
     * @param $content
     * @return array
     * @throws Exception
     */
    protected function mtMessageRequestBody($phone, $content)
    {
        $message = $this->messageRequestBody($phone);
        $message['Messages']['Content'] = $content;
        $message['Messages']['MessageType'] = 'Content';
        $message['Messages']['Signature'] = $this->sign($this->signData($message));

        return $message;
    }

    /**
     * Produce Message Body to Send OTP
     *
     * @param $phone
     * @return array
     * @throws Exception
     */
    protected function otpMessageRequestBody($phone)
    {
        $message = $this->messageRequestBody($phone);
        $message['Messages']['MessageType'] = 'Verification';
        $message['Messages']['Signature'] = $this->sign($this->signData($message));

        return $message;
    }

    /**
     * Produce Message Body to Confirm OTP
     *
     * @param $phone
     * @param $code
     * @return array
     * @throws Exception
     */
    protected function confirmOtpMessageRequestBody($phone, $code)
    {
        $message = $this->messageRequestBody($phone);
        $message['Messages']['Content'] = $code;
        $message['Messages']['MessageType'] = 'Subscription';
        $message['Messages']['Signature'] = $this->sign($this->signData($message));

        return $message;
    }

    /**
     * Produce DateTime for FanapPlus Service.
     *
     * @param int $addMinutes
     * @return string
     */
    protected function dateTime($addMinutes = 0)
    {
        $dateTime = Carbon::now('UTC')->addMinutes($addMinutes)->format('Y-m-d\TH:i:s.u');
        return substr($dateTime, 0, -3) . 'Z';
    }
}