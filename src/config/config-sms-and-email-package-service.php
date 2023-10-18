<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Service Wrapper location
    |--------------------------------------------------------------------------
    |
    | You can define the wrappers where should be created
    |
    */
    "wrapper_creation_path" => "Services",

    /*
    |--------------------------------------------------------------------------
    | otp service options
    |--------------------------------------------------------------------------
    |
    | you can define otp service options
    | also this settings can fetch from env
    |
    */
    'otp' => [
        'length' => env('OTP_LENGTH',5),
        'only_digits' => env('OTP_ONLY_DIGITS',true),
        'validity' => env('OTP_VALIDITY_TIME',60),
        'max_attempts' => env('OTP_MAX_ATTEMPTS',3),
        'opt_message' =>  "OTP for Savyour is: {TOKEN}. For queries, whatsapp us at 03018262530",
    ],

    /*
    |--------------------------------------------------------------------------
    | otp Model class
    |--------------------------------------------------------------------------
    |
    | you can add yor otp model class with this columns having
    | [token,user_id,mobile_number,validity,sending_attempts,validation_attempts,is_verified,is_expired,generated_date]
    |
    |
    */
    'OTPModelClass' => '',

    /*
    |--------------------------------------------------------------------------
    | otp Service errors you can override this errors
    |--------------------------------------------------------------------------
    |
    |
    */
    'errors' => [
        "service_wrapper_errors" => [
            "SUCCESS"=>"SUCCESS",
            "NO_SERVICE_CALLED"=>"NO_SERVICE_CALLED",
            "ERROR_FROM_SERVICE"=>"ERROR_FROM_SERVICE",
            "ERROR_IN_CATCH_BLOCK"=>"ERROR_IN_CATCH_BLOCK",
        ],
        "opt_service_errors" =>[
            "validation_attempts_error_message" => "You have reached maximum limit of OTP failed attempts You can retry after 24 hours.",
            "sending_attempts_error_message" => "You have reached maximum limit of receiving OTP's. You can retry after {duration} minutes.",
            "all_service_failure" => "OTP service failure. Retry again.",
            "otp_not_found" => 'Wrong OTP entered Or OTP is expired',
            "otp_expired" => 'OTP is expired',
            "wrong_otp" => 'OTP is expired',
            "no_service_available" => 'No service available',

        ]

    ],

];
