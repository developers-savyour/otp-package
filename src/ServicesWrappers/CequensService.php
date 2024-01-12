<?php

namespace Savyour\SmsAndEmailPackage\ServicesWrappers;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client as GuzzleClient;

class CequensService
{
    private $settings,$debug,$className;
    public static $SERVICE_RESPONSE_CODE_LABELS = [];
    public static $OTP_SERVICE_ERROR = [];

    public function __construct()
    {
        $this->settings = config('config-sms-and-email-package-service.services_constants.cequens_sms_api');
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
                "message"=>$this->className.' SMS SERVICE INACTIVE ',
                "code"=>500,
            ];
            Log::info($this->className.'SMS SERVICE INACTIVE : status '.$this->settings['active_mode'], $errorData);
            return $errorData;
        }

        try {

            // Create an array with request parameters
            $param = [
                'senderName' => $this->settings['sender'],
                'messageType' => 'text',
                'shortURL' => false,
                'messageText' => $msg,
                'recipients' => $phone,
            ];
            // Encode the parameters as JSON
            $body = json_encode($param);
            // Create a Guzzle HTTP client
            $client = new GuzzleClient();

            // Send a POST request to the specified URL with JSON body
            $response = $client->request('POST', $this->settings['url'], [
                'body' => $body,
                'headers' =>  [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer " .$this->settings['token'],
                    'accept' => 'application/json'
                ]
            ]);
            // Decode the response JSON content
            $responseCurl = json_decode($response->getBody()->getContents(), true);
            // Check the response status
            $status = isset($responseCurl['replyCode']) && $responseCurl['replyCode'] == 0;
            $serviceMessage = isset($responseCurl['replyMessage']) ? $responseCurl['replyMessage'] : '';
            // Prepare the response data
            $response = [
                'status' => $status,
                'response' =>  ["raw"=>$responseCurl,"message"=>$serviceMessage],
                'service_error_type' => $status ? self::$OTP_SERVICE_ERROR['SUCCESS']:self::$OTP_SERVICE_ERROR['ERROR_FROM_SERVICE'],
            ];
            // Log debug information if debug mode is enabled
            if ($this->debug) {
                Log::info($this->className.' SMS API DEBUG: ', ['Response' => $response,'request'=>$body,'token'=>$this->settings['token']]);
            }

            return $response;

        } catch (\Exception $e) {
            // Handle exceptions and prepare error data
            $errorData = [
                "status" => false,
                "service_error_type"=>self::$OTP_SERVICE_ERROR['ERROR_IN_CATCH_BLOCK'],
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
