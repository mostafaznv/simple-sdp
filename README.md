# SimpleSDP
SimpleSDP is a laravel package that makes it easy to send SMS and OTP(One Time Password) via SDP.


> Note: farsi [documentation](README.fa.md)

## Some of the features for SimpleSDP:
- Support multiple drivers
- Configurable
- Customize models
- Logging system
- Translatable
- Auto discovery (only laravel 5.5+)

## Requirements:
- Laravel 5.3 or higher
- PHP 5.6.4 or higher

## Available Drivers
- AKO (ako)
- SSDP (ssdp)
- WAP (wap) `coming soon`

## Installation

1. ##### Install the package via composer:
    ```shell
    composer require mostafaznv/simple-sdp
    ```

2. ##### Register Provider and Facade in config/app.php (Required for Laravel 5.3 and 5.4):
    ```shell
    'providers' => [
      ...
      Mostafaznv\SimpleSDP\SimpleSDPServiceProvider::class,
    ],
    
    
    'aliases' => [
      ...
      'SimpleSDP' => Mostafaznv\SimpleSDP\SimpleSDP::class,
    ]
    ```

3. ##### Publish config, translation and migrations:
    ```shell
    php artisan vendor:publish --provider="Mostafaznv\SimpleSDP\SimpleSDPServiceProvider"
    ```

4. ##### Create tables:
    ```shell
    php artisan migrate
    ```

4. ##### Done

> to set your own config, just open config/simple-sdp.php and set your personal configurations.

## Usage
```php
try {   
   $msisdn = '9891200012345';
   $data = [
       'content_id' => 12,
       'message' => 'message text'
   ];   
   
   $sdp = app('SimpleSDP')->sendMt($msisdn, $data); // use simple sdp instance with app() method.

   // or

   $sdp = \SimpleSDP::sendMt($msisdn, $data); // send mt to default driver (simple-sdp can load default driver from config file).
   
   // or

   $sdp = \SimpleSDP::AKO()->sendMt($msisdn, $data); // define driver manually
   
   // or
   
   $sdp = \SimpleSDP::make(new \Mostafaznv\SimpleSDP\SSDP\SSDP())->sendMt($msisdn, $data); // define driver manually
   
   return response()->json($sdp, 200);
  
} 
catch (\Exception $e) {   
    return response()->json($e->getMessage(), 500);
}
```

## Dynamic Configuration
You can pass a new config array to SimpleSDP instance to replace with default config array.
```php
// default config
$sdp = app('SimpleSDP')->AKO()->sendMt($msisdn, $data); 

// new config
$config = config('simple-sdp.new_config');
$sdp = app('SimpleSDP')->AKO($config)->sendMt($msisdn, $data);

```

## Custom Trans prefix (only available in ako)
Sometimes you want change messages in some cases based on your project logic, with this option you can do it.

1. Change config file
    ```php
    ...
    'trans_prefix' => 'new_prefix'
    ```
2. Edit messages array in `resources/lang/vendor/simple-sdp/en/messages.php`
    ```php
    ...
    'new_prefix' => [
       'welcome'     => 'welcome',
       'inform'      => 'inform',
       'guide'       => 'guide',
       'unsub-guide' => 'unsub guide',
       'unsub'       => 'unsub',
       'logout_text' => 'logout text',
    ]
    ```

## Available Functions
1. #### Send MT
    ```php
    $msisdn = '9891200012345';
    $data = [
        'content_id' => 12,
        'message' => 'message text',
        'is_free' => true', // optional
    ];
    
    app('SimpleSDP')->sendMt($msisdn, $data);
    ```
    
2. #### Charge
    ```php
    $msisdn = '9891200012345';
    $data = [
        'content_id' => 12
    ];
    
    app('SimpleSDP')->charge($msisdn, $data);
    ```
    
3. #### Send OTP
    ```php
    $msisdn = '9891200012345';
    $data = [
        'content_id' => 12
    ];
    
    app('SimpleSDP')->sendOtp($msisdn, $data);
    ```
    
4. #### Confirm OTP
    ```php
    $msisdn = '9891200012345';
    $code = 2323;
    $data = [
        'content_id' => 12
    ];
    
    app('SimpleSDP')->confirmOtp($msisdn, $code, $data);
    ```
    
5. #### Delivery
    ```php
    app('SimpleSDP')->delivery($request); // Request $request
    ```
    
6. #### Batch Delivery
    ```php
    app('SimpleSDP')->batchDelivery($request); // Request $request
    ```
    
7. #### Incoming Message
    ```php
    app('SimpleSDP')->income($request); // Request $request
    ```
    
8. #### Batch Mo
    ```php
    app('SimpleSDP')->batchMo($request); // Request $request
    ```
    

## Contributors
- Mostafa Zeinivand [@mostafaznv](https://github.com/mostafaznv)
- Faezeh Ghorbannezhad [@Ghorbannezhad](https://github.com/Ghorbannezhad)
- SamssonApps [@SamssonApps](https://github.com/SamssonApps)


## Changelog
Refer to the [Changelog](CHANGELOG.md) for a full history of the project.

## License
This software is released under [The MIT License (MIT)](LICENSE).

(c) 2018 Mostafaznv, All rights reserved.