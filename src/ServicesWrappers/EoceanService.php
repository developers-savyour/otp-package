<?php

namespace Savyour\SmsAndEmailPackage\ServicesWrappers;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;

class EoceanService
{
    private $settings,$debug,$className,$eOceanToken,$settingsModelClass;
    public static $SERVICE_RESPONSE_CODE_LABELS = [];
    public static $OTP_SERVICE_ERROR = [];
    
    public function __construct()
    {
        $this->settingsModelClass =  config('config-sms-and-email-package-service.SettingsModelClass');
        $this->eOceanToken = $this->settingsModelClass::get('eocean_auth_token');
        $this->settings = config('config-sms-and-email-package-service.services_constants.eocean_sms_api');
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
            // removing plus sign from number
            $phone = str_replace('+', '', $phone);

            $headers = [
                "x-access-token" => $this->eOceanToken,
                "Content-Type" => 'application/json',
            ];
            
            $param = [
                "to" => $phone,
                "from" => $this->settings['sender'],
                "text" => $msg,
                "messageId" => uniqid()
            ];

            $body = json_encode($param);
            $client = new GuzzleClient($headers);
            $response = $client->request('POST', $this->settings['url'].'send', [
                'body' => $body,
                'headers' => $headers
            ]);
            $responseCurl = json_decode( $response->getBody()->getContents() ,true);
            $status = (isset($responseCurl['statusCode']) && $responseCurl['statusCode'] == 200) ?  true : false ;

            $response = [
                'status' => $status,
                'service_error_type' => ($status) ? self::$OTP_SERVICE_ERROR['SUCCESS']:self::$OTP_SERVICE_ERROR['ERROR_FROM_SERVICE'],
            ];

            // Log debug information if debug mode is enabled
            if ($this->debug) {
                Log::info($this->className.' SMS API DEBUG: ', [
                    'Response' => $response,
                    'body'=>$body,
                    'headers'=>$headers,
                    'rawResponse' => $responseCurl,
                    'url'=> $this->settings['url'].'send',
                ]);
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
