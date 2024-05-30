<?php

namespace Savyour\SmsAndEmailPackage\ServicesWrappers;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    private $settings,$debug,$className;
    private $extraData = [];
    public static $SERVICE_RESPONSE_CODE_LABELS = [];
    public static $OTP_SERVICE_ERROR = [];


    public function __construct()
    {
        $this->settings = config('config-sms-and-email-package-service.services_constants.twilio');
        $this->debug = config('config-sms-and-email-package-service.otp.otp_debug_mode');
        self::$OTP_SERVICE_ERROR = config('config-sms-and-email-package-service.errors.service_wrapper_errors');
        $this->className = __class__;
    }

    public function setExtraData(array $data)
    {
        $this->extraData = $data;
    }

    public function send($phone, $msg)
    {

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

            // checking testing mode is not active
            if(!$this->settings['testing_mode'])
            {
                $twilio = new Client($this->settings['account_id'], ($this->settings['auth_token']));
                $message = $twilio->messages->create($phone, // to
                    [
                        "body" => $msg,
                        "from" => $this->settings['phone_number']
                    ]
                );
            }
            else
            {
                // testing payload
                $message = collect(json_decode('{"body":"Sent from your Twilio trial account - Your OTP for Savyour is: 27174. For queries, Call us at 03347287284","numSegments":"1","direction":"outbound-api","from":"+15093003942","to":"+923102607803","dateUpdated":"2022-10-31 15:22:34","price":null,"errorMessage":null,"uri":"/2010-04-01/Accounts/ACdc331bd894cda29a448875e1dededd27/Messages/SM4d23e8450aa20bbeb5615d4bfb87eb2d.json","accountSid":"ACdc331bd894cda29a448875e1dededd27","numMedia":"0","status":"queued","messagingServiceSid":null,"sid":"SM4d23e8450aa20bbeb5615d4bfb87eb2d","dateSent":null,"dateCreated":"2022-10-31 15:22:34","errorCode":null,"priceUnit":"USD","apiVersion":"2010-04-01","subresourceUris":{"media":"/2010-04-01/Accounts/ACdc331bd894cda29a448875e1dededd27/Messages/SM4d23e8450aa20bbeb5615d4bfb87eb2d/Media.json"}}'));
                $message->status = 'queued';
            }

            $status = ($message->status == 'queued' || $message->status == 'true' || $message->status == 'accepted' || $message->status == 'completed') ? true : false;

            $response = [
                'status' => $status,
                'service_error_type'=> ($status)? self::$OTP_SERVICE_ERROR['SUCCESS']:self::$OTP_SERVICE_ERROR['ERROR_FROM_SERVICE'],
            ];

            if($this->debug){
                Log::info('trying '.$this->className.' service');
                Log::info('in try block with this params '.$this->className.' ',[
                    "to" =>$phone,
                    "message_body" =>$msg,
                    "from" =>$this->settings['phone_number'],
                    'sid' => $this->settings['account_id'],
                    'token' => $this->settings['auth_token'],
                ]);
                Log::info($this->className.' SMS API DEBUG RAW: ', $message->toArray());
                Log::info($this->className.' SMS API DEBUG MODE: ', $response);
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
