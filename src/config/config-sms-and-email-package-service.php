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
        'otp_message' =>  env('OTP_MESSAGE','Token is: {TOKEN}'),
        'otp_debug_mode' =>  env('OTP_DEBUG_MODE',true),
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
    'OTPModelClass' => App\Models\OTP::Class,


    /*
    |--------------------------------------------------------------------------
    | Settings Model class
    |--------------------------------------------------------------------------
    |
    */
    'SettingsModelClass' => App\Models\Setting::class,

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

    /*
    |--------------------------------------------------------------------------
    | otp Services env vars
    |--------------------------------------------------------------------------
    |
    |
    */
    "services_constants" =>[
        'monty_sms_api' => [
            'url' => env('MONTY_SMS_API_URL'),
            'username' => env('MONTY_SMS_API_USERNAME'),
            'api_id' => env('MONTY_SMS_API_ID'),
            'sender' => env('MONTY_SMS_API_SENDER'),
            'active_mode' => env('MONTY_ACTIVE_MODE',false)
        ],
        'convex_sms_api' => [
            'url' => env('CONVEX_SMS_API_URL'),
            'secretkey' => env('CONVEX_SECRET_KEY'),
            'apikey' => env('CONVEX_API_KEY'),
            'sender' => env('CONVEX_SMS_API_SENDER'),
            'active_mode' => env('CONVEX_ACTIVE_MODE',false)
        ],
        'eocean_sms_api' => [
            'auth_url' => env('EOCEAN_SMS_API_AUTH_URL'),
            'auth_token' => env('EOCEAN_SMS_API_AUTH_URL'),
            'url' => env('EOCEAN_SMS_API_URL'),
            'sender' => env('EOCEAN_SMS_API_SENDER'),
            'active_mode' => env('EOCEAN_ACTIVE_MODE',false)
        ],
        'm3tech_sms_api' => [
            'url' => env('M3TECH_SMS_API_URL'),
            'auth_token' => env('M3TECH_AUTH'),
            'sender' => env('M3TECH_MSG_HEADER'),
            'active_mode' => env('M3TECH_ACTIVE_MODE',false),
            'testing_mode' => env('M3TECH_SMS_TESTING_MODE',false),
        ],
        'cequens_sms_api' => [
            'auth_url' => env('CEQUENS_AUTH_URL'),
            'user_name' => env('CEQUENS_USER_NAME'),
            'api_key' => env('CEQUENS_API_KEY'),
            'url' => env('CEQUENS_SMS_API_URL'),
            'auth' => env('CEQUENS_AUTH'),
            'sender' => env('CEQUENS_SENDER'),
            'token' => env('CEQUENS_TOKEN'),
            'active_mode' => env('CEQUENS_ACTIVE_MODE',false),
        ],
        'twilio' => [
            'account_id' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'phone_number' => env('TWILIO_PHONE_NUMBER'),
            'verification_id' => env('TWILIO_VERIFICATION_SID'),
            'active_mode' => env('TWILIO_ACTIVE_MODE',false),
            'testing_mode' => env('TWILIO_TESTING_MODE',false),
        ],
    ]
];

