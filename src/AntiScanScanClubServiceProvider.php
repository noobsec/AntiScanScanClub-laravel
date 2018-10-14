<?php

namespace noobsec\AntiScanScanClub;

use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider;

class AntiScanScanClubServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $source = realpath($raw = __DIR__.'/../config/antiscanscanclub.php') ?: $raw;
        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([$source => config_path('antiscanscanclub.php')]);
        }

        if ($this->app instanceof LaravelApplication && ! $this->app->configurationIsCached()) {
            $this->mergeConfigFrom($source, 'antiscanscanclub');
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        // 
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['antiscanscanclub'];
    }
}
