<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SDP Driver
    |--------------------------------------------------------------------------
    |
    | The default mechanism for handling SDP
    |
    | Supported: ako, ssdp
    |
    */

    'driver' => 'ssdp',

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | You create your own or use package default config.
    |
    */

    'models' => [
        'mobile_terminated' => \Mostafaznv\SimpleSDP\Models\MobileTerminated::class,
        'mobile_originated' => \Mostafaznv\SimpleSDP\Models\MobileOriginated::class,
        'confirm_otp_logs'  => \Mostafaznv\SimpleSDP\Models\ConfirmOtpLog::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Status
    |--------------------------------------------------------------------------
    |
    | Enable/Disable logging.
    |
    */

    'log' => true,


    /*
    |--------------------------------------------------------------------------
    | Log path
    |--------------------------------------------------------------------------
    |
    | You can specify log path to log all events.
    |
    */

    'log_path' => storage_path('logs/simple-sdp'),


    /*
    |--------------------------------------------------------------------------
    | AKO Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration values for ako
    |
    */

    'ako' => [
        'baseurl'                   => 'http://10.20.9.187:8700/samsson-gateway/',
        'partner_code'              => 'partner_code',
        'service_id'                => '12345',
        'short_code'                => '1234567',
        'username'                  => 'username',
        'password'                  => 'password',
        'subscription_short_code'   => '123',
        'unsubscription_short_code' => '1234',
        'charging_code'             => 'charge_code',
        'sub_charging_code'         => 'sub_charging_code',
        'unsub_charging_code'       => 'unsub_charging_code',
        'message'                   => '',
        'description'               => 'DeliveryChannel=APP|DiscoveryChannel=APP|origin=:short_code|contentId=:content_id',
        'amount'                    => 3000,
        'currency'                  => 'RLS',
        'service_name'              => 'service_name',
        'request_limit'             => 100,
        'confirm_otp_timeout'       => 5,
        'is_free'                   => true,
        'trans_prefix'              => 'mt',
        'database'                  =>null,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | SSDP Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration values for ako
    |
    */

    'ssdp' => [
        'baseurl'                   => 'http://10.20.9.135:8600/samsson-sdp/',
        'partner_code'              => 'partner_code',
        'service_id'                => '123456',
        'short_code'                => '1234567',
        'username'                  => 'username',
        'password'                  => 'password',
        'charging_code'             => 'charging_code',
        'subscription_short_code'   => '123',
        'unsubscription_short_code' => '12345',
        'sub_charging_code'         => 'sub_charging_code',
        'unsub_charging_code'       => 'unsub_charging_code',
        'message'                   => 'test',
        'confirm_otp_timeout'       => 5,
        'database'                  =>null,
    ]
];
