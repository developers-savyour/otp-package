<?php

namespace Savyour\SmsAndEmailPackage\ServicesWrappers;

use Illuminate\Support\Facades\Log;

class M3TechService
{
    private $settings,$debug,$className;
    public static $OTP_SERVICE_ERROR = [];
    private static $SERVICE_RESPONSE_CODE_LABELS = [
        "0" => "Message Sent Successfully",
        "-1"=> "Authentication Failed",
        "-2"=> "Invalid Mobile No.",
        "-3"=> "Invalid Message ID.",
        "-4"=> "Blank Message",
        "-5"=> "Invalid SMS Type",
        "-6"=> "Invalid Message Header",
        "-7"=> "Account Expired",
        "-8"=> "Invalid Telco",
        "-9"=> "IP Address not allowed",
        "-10"=> "Duplicate Message Id",
        "-11"=> "Invalid Handset Port",
        "-12"=> "Invalid Channel Data",
        "-13"=> "Block SMS",
    ];

    public function __construct()
    {
        $this->settings = config('config-sms-and-email-package-service.services_constants.m3tech_sms_api');
        $this->debug = config('config-sms-and-email-package-service.otp.otp_debug_mode');
        self::$OTP_SERVICE_ERROR = config('config-sms-and-email-package-service.errors.service_wrapper_errors');
        $this->className = __class__;

    }

    public function send($phone, $msg)
    {
        // checking the sms service is enable
        if(!$this->settings['active_mode'])
        {
            $errorData = [
                "status"=>false,
                "service_error_type"=>self::$OTP_SERVICE_ERROR['NO_SERVICE_CALLED'],
                "message"=>$this->className.' SMS SERVICE INACTIVE',
                "code"=>500,
            ];
            Log::info($this->className.'SMS SERVICE INACTIVE : status '.$this->settings['active_mode'], $errorData);
            return $errorData;
        }

        try {
        // Remove '+' character from the phone number
        $phone = str_replace('+', '', $phone);

        // Create an array with request parameters
        $param = [
            'MobileNo' => $phone,
            'MsgId' => uniqid(),
            'SMS' => $msg,
            'MsgHeader' => $this->settings['sender'],
            "SMSChannel"=>"",
            "Telco"=>""
        ];

        // Encode the parameters as JSON
        $body = json_encode($param);

        $responseCurl = '';
        // checking testing mode
        if($this->settings['testing_mode'])
        {
            $responseCurl = [
                "ResponseCode"=>'0',
                "Description"=>'Message Sent Successfully',
                "Operator"=> 'ZONG',
                'TransactionID'=> '6634f84cd5f4e',
            ];
        }
        else
        {
            // Create a Guzzle HTTP client
            $client = new \GuzzleHttp\Client();

            // Send a POST request to the specified URL with JSON body
            $response = $client->request('POST', $this->settings['url'], [
                'body' => $body,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Basic " .$this->settings['auth_token']
                ]
            ]);

            // Decode the response JSON content
            $responseCurl = json_decode($response->getBody()->getContents(), true);
        }

        // Check the response status
        $status = isset($responseCurl['ResponseCode']) && $responseCurl['ResponseCode'] == 0;
        $serviceMessage = (isset($responseCurl['ResponseCode']) && self::$SERVICE_RESPONSE_CODE_LABELS[$responseCurl['ResponseCode']]) ? self::$SERVICE_RESPONSE_CODE_LABELS[$responseCurl['ResponseCode']] : '' ;

        // Prepare the response data
        $response = [
            'status' => $status,
            'response' => ["raw"=>$responseCurl,"message"=>$serviceMessage],
            'service_error_type' => $status ? self::$OTP_SERVICE_ERROR['SUCCESS'] : self::$OTP_SERVICE_ERROR['ERROR_FROM_SERVICE'],
        ];

        // Log debug information if debug mode is enabled
        if ($this->debug) {
            Log::info($this->className.' SMS API DEBUG: ', ['Response' => $response,'request'=>$body]);
        }

        return $response;
        } catch (\Exception $e) {
            $errorData = [
                "status"=>false,
                "service_error_type"=>self::$OTP_SERVICE_ERROR['ERROR_IN_CATCH_BLOCK'],
                "message"=>$e->getMessage(),
                "code"=> $e->getCode(),
                "file"=> $e->getFile(),
                "line"=>$e->getLine()
            ];
            Log::info($this->className.' SMS API CATCH: ', $errorData);
            return $errorData;
        }

    }

}

