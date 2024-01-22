<?php

namespace Savyour\SmsAndEmailPackage\Commands;

use Illuminate\Console\Command;
use Savyour\SmsAndEmailPackage\Helpers\Publisher;

class PublishConfig extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'savyour-otp-package:publish-config';
    protected $signature = 'savyour-otp-package:publish-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish config';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('Publish config files');
        
        (new Publisher($this))->publishFile(
            realpath(__DIR__.'/../config/').'/config-sms-and-email-package-service.php',
            base_path('config'),
            'config-sms-and-email-package-service.php'
        );
        
    }
    
}