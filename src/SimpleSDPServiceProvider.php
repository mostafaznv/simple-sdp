<?php

namespace Mostafaznv\SimpleSDP;

use Illuminate\Support\ServiceProvider;

class SimpleSDPServiceProvider extends ServiceProvider
{
    const VERSION = '0.0.2';

    // todo - test


    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__ . '/../translations', 'simple-sdp');

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../config/config.php' => config_path('simple-sdp.php')], 'config');
            $this->publishes([__DIR__ . '/../migrations/' => database_path('migrations')], 'migrations');
            $this->publishes([__DIR__ . '/../translations/' => resource_path('lang/vendor/simple-sdp')]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'simple-sdp');

        $this->app->singleton('SimpleSDP', function() {
            $resolver = new SdpResolver();

            return $resolver;
        });
    }
}