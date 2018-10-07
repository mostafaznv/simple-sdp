# SimpleSDP
سیمپل‌ اس‌دی‌پی یک پکیج لاراول است که ارسال پیامک و تولید کلمه عبور یکبار مصرف به وسیله سرویس‌های اس‌دی‌پی را برای شما ساده می‌کند

> توجه: انگلیسی [داکیومنت](README.md)

## برخی از ویژگی‌های مهم پکیج
- پشتیبانی از درایورهای مختلف به صورت همزمان
- قابلیت شخصی سازی
- قابلیت شخصی سازی مدل ها
- سیستم ثبت لاگ
- قابلیت ترجمه
- شناسایی خودکار پکیج (برای لاراول ۵.۵ و بالاتر)

## نیازمندی‌ها
- لاراول ۵.۳ یا بالاتر
- پی‌اچ‌پی ۵.۶.۴ یا بالاتر

## درایورهای پشتیبانی شده
- AKO (ako)
- SSDP (ssdp)
- WAP (wap) `به زودی`

## روش نصب

1. ##### نصب پکیج از طریق کامپوزر
    ```shell
    composer require mostafaznv/simple-sdp
    ```

2. ##### رجیستر کردن پرووایدر و فساد (برای لاراول ۵.۵ به بالا اختیاری‌ست)
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

3. ##### انتشار فایل تنظیمات، دیتابیس و ترجمه
    ```shell
    php artisan vendor:publish --provider="Mostafaznv\SimpleSDP\SimpleSDPServiceProvider"
    ```

4. ##### ایجاد جدول ها
    ```shell
    php artisan migrate
    ```

4. ##### تمام

> برای انجام تنظیمات شخصی خود به فایل `config/simple-sdp.php` مراجعه کنید

## روش استفاده
```php
try {   
   $msisdn = '9891200012345';
   $data = [
       'content_id' => 12,
       'message' => 'message text'
   ];   
   
   $sdp = app('simple-sdp')->sendMt($msisdn, $data); // فراخوانی سیمپل اس‌دی‌پی به کمک تابع app()

   // یا

   $sdp = \SimpleSDP::sendMt($msisdn, $data); // ارسال پیام از طریق فساد
   
   // یا

   $sdp = \SimpleSDP::AKO()->sendMt($msisdn, $data); // تعریف درایور به صورت دستی و در زمان اجرا
   
   // یا
   
   $sdp = \SimpleSDP::make(new \Mostafaznv\SimpleSDP\SSDP\SSDP())->sendMt($msisdn, $data); // تعریف درایور به صورت دستی
   
   return response()->json($sdp, 200);
  
} 
catch (\Exception $e) {   
    return response()->json($e->getMessage(), 500);
}
```

## توابع قابل استفاده
1. #### Send MT
    ```php
    $msisdn = '9891200012345';
    $data = [
        'content_id' => 12,
        'message' => 'message text'
    ];
    
    app('simple-sdp')->sendMt($msisdn, $data);
    ```
    
2. #### Charge
    ```php
    $msisdn = '9891200012345';
    $data = [
        'content_id' => 12
    ];
    
    app('simple-sdp')->charge($msisdn, $data);
    ```
    
3. #### Send OTP
    ```php
    $msisdn = '9891200012345';
    $data = [
        'content_id' => 12
    ];
    
    app('simple-sdp')->sendOtp($msisdn, $data);
    ```
    
4. #### Confirm OTP
    ```php
    $msisdn = '9891200012345';
    $code = 2323;
    $data = [
        'content_id' => 12
    ];
    
    app('simple-sdp')->confirmOtp($msisdn, $code, $data);
    ```
    
5. #### Delivery
    ```php
    app('simple-sdp')->delivery($request); // Request $request
    ```
    
6. #### Batch Delivery
    ```php
    app('simple-sdp')->batchDelivery($request); // Request $request
    ```
    
7. #### Incoming Message
    ```php
    app('simple-sdp')->income($request); // Request $request
    ```
    
8. #### Batch Mo
    ```php
    app('simple-sdp')->batchMo($request); // Request $request
    ```
    

## توسعه دهندگان
- Mostafa Zeinivand [@mostafaznv](https://github.com/mostafaznv)
- Faezeh Ghorbannezhad [@Ghorbannezhad](https://github.com/Ghorbannezhad)
- SamssonApps [@SamssonApps](https://github.com/SamssonApps)


## تغییرات
تمام تغیرات نسخه های مختلف در صفحه [تغییرات](CHANGELOG.md) قابل پیگیری هستند.

## License
این نرم افزار تحت لیسانس [ام آی ای](LICENSE) توسعه یافته است.

(c) 2018 Mostafaznv, All rights reserved.