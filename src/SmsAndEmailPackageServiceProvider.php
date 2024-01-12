<?php

namespace Savyour\SmsAndEmailPackage;

use Illuminate\Support\ServiceProvider;
use Savyour\SmsAndEmailPackage\Commands\CreateSmsWrapper;
use Savyour\SmsAndEmailPackage\Commands\PublishConfig;

class SmsAndEmailPackageServiceProvider extends ServiceProvider
{

    protected const configFileName = 'config-sms-and-email-package-service';
    protected const configFilePath = __DIR__.'/config/'.self::configFileName.'.php';


    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        //lood app instance
        $app = $this->app;
        // load configuration
        $this->bootConfig();
        // load commnads
        $this->registerCommands();

    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            self::configFilePath, self::configFileName
        );
    }


    /**
     * Booting configure.
     */
    protected function bootConfig()
    {

        $configFileName = self::configFileName;
        $path = self::configFilePath;
        $this->mergeConfigFrom($path, $configFileName);

        if (function_exists('config_path')) {
            $this->publishes([$path => config_path($configFileName.'.php'),'config']);
        }
    }

    /**
     * Register commands
     */
    protected function registerCommands()
    {
        // checking the command is running in console
        if (! $this->app->runningInConsole()) return;
        // register commands
        $this->commands([
           CreateSmsWrapper::class,
           PublishConfig::class,
        ]);

    }

}