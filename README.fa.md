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
- Rashin
- FanapPlus

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
   
   $sdp = app('SimpleSDP')->sendMt($msisdn, $data); // فراخوانی سیمپل اس‌دی‌پی به کمک تابع app()

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

## تنظیم کانفیگ درایور به صورت دستی(فقط در حالت آکو)
شما میتوانید هنگام ایجاد یه آبجکت از سیمپل اس‌دی‌پی فایل کانفیگ جدید خود را به ورودی تابع دهید تا با مقادیر پیشفرض کانفیگ که از طریق `config/simple-sdp.php` تنظیم شده اند جایگزین کنید
```php
// تنظیمات پیشفرض
$sdp = app('SimpleSDP')->AKO()->sendMt($msisdn, $data); 

// تنظیمات جدید و داینامیک
$config = config('simple-sdp.new_config');
$sdp = app('SimpleSDP')->AKO($config)->sendMt($msisdn, $data);

```

## تغییر دادن ترجمه پیام‌ها
بعضی وقتها نیاز دارید که بر اساس منطق برنامه خود، در حالت های مختلف پیام های مختلفی را به کاربر نشان دهید. با کمک این قابلیت میتوانید این کار را انجام دهید

1. ویرایش فایل تنظیمات
    ```php
    ...
    'trans_prefix' => 'new_prefix'
    ```
2. افزودن پیشوند جدید به فایل `resources/lang/vendor/simple-sdp/en/messages.php`
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


## توابع قابل استفاده
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
2. #### Send Batch MT
    ```php
    $msisdn = ['9891200012345', '9891200015432'];
    $data = [
        'content_id' => 12,
        'message' => 'message text'
    ];
    
    app('SimpleSDP')->sendBatchMt($msisdn, $data);
    ```
    
3. #### Charge
    ```php
    $msisdn = '9891200012345';
    $data = [
        'content_id' => 12
    ];
    
    app('SimpleSDP')->charge($msisdn, $data);
    ```
    
4. #### Send OTP
    ```php
    $msisdn = '9891200012345';
    $data = [
        'content_id' => 12
    ];
    
    app('SimpleSDP')->sendOtp($msisdn, $data);
    ```
    
5. #### Confirm OTP
    ```php
    $msisdn = '9891200012345';
    $code = 2323;
    $data = [
        'content_id' => 12
    ];
    
    app('SimpleSDP')->confirmOtp($msisdn, $code, $data);
    ```
    
6. #### Delivery
    ```php
    app('SimpleSDP')->delivery($request); // Request $request
    ```
    
7. #### Batch Delivery
    ```php
    app('SimpleSDP')->batchDelivery($request); // Request $request
    ```
    
8. #### Incoming Message
    ```php
    app('SimpleSDP')->income($request); // Request $request
    ```
    
9. #### Batch Mo
    ```php
    app('SimpleSDP')->batchMo($request); // Request $request
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