<?php

namespace Savyour\SmsAndEmailPackage\Facades;

use Illuminate\Support\Facades\Facade;

class OTP extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'otp';
    }
}