<?php

namespace Savyour\SmsAndEmailPackage\ServicesWrappers;

class M3TechService
{
    private $token, $sender, $userid, $password, $url, $debug, $activeMode,$className;
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
        $this->sender = constants('m3tech_sms_api.sender');
        $this->token = constants('m3tech_sms_api.auth_token');
        $this->url = constants('m3tech_sms_api.url');
        $this->debug = constants('m3tech_sms_api.debug_mode');
        $this->activeMode = constants('m3tech_sms_api.active_mode');
        $this->className = __class__;

    }

    public function send($phone, $msg)
    {
        // checking the sms service is enable
        if(!$this->activeMode)
        {
            $errorData = [
                "status"=>false,
                "service_error_type"=>OTPService::$SMS_SERVICE_ERROR_TYPES[1],
                "message"=>$this->className.' SMS SERVICE INACTIVE',
                "code"=>500,
            ];
            Log::info($this->className.' SMS SERVICE INACTIVE: ', $errorData);
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
            'MsgHeader' => $this->sender,
            "SMSChannel"=>"",
            "Telco"=>""
        ];

        // Encode the parameters as JSON
        $body = json_encode($param);

        // Create a Guzzle HTTP client
        $client = new \GuzzleHttp\Client();

        // Send a POST request to the specified URL with JSON body
        $response = $client->request('POST', $this->url, [
            'body' => $body,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => "Basic " .$this->token
            ]
        ]);

        // Decode the response JSON content
        $responseCurl = json_decode($response->getBody()->getContents(), true);

        // Check the response status
        $status = isset($responseCurl['ResponseCode']) && $responseCurl['ResponseCode'] == 0;
        $serviceMessage = (isset($responseCurl['ResponseCode']) && self::$SERVICE_RESPONSE_CODE_LABELS[$responseCurl['ResponseCode']]) ? self::$SERVICE_RESPONSE_CODE_LABELS[$responseCurl['ResponseCode']] : '' ;

        // Prepare the response data
        $response = [
            'status' => $status,
            'response' => ["raw"=>$responseCurl,"message"=>$serviceMessage],
            'service_error_type' => $status ? OTPService::$SMS_SERVICE_ERROR_TYPES[0] : OTPService::$SMS_SERVICE_ERROR_TYPES[2],
        ];

        // Log debug information if debug mode is enabled
        if ($this->debug) {
            Log::info($this->className.' SMS API DEBUG: ', ['Response' => $response,'request'=>$body]);
        }

        return $response;
        } catch (\Exception $e) {
            // Handle exceptions and prepare error data
            $errorData = [
                "status" => false,
                "service_error_type" => OTPService::$SMS_SERVICE_ERROR_TYPES[3],
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
                "file" => $e->getFile(),
                "line" => $e->getLine()
            ];

            // Log error information
            Log::info($this->className.' SMS API CATCH: ', $errorData);

            return $errorData;
        }

    }

}
