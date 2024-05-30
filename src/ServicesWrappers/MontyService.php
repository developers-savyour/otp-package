<?php

namespace Savyour\SmsAndEmailPackage\ServicesWrappers;


class MontyService
{
    private $sender, $username, $apiID, $url, $debug,$activeMode;
    private $extraData = [];

    public function __construct()
    {
        // monty_sms_api
        $this->sender = constants('monty_sms_api.sender');
        $this->username = constants('monty_sms_api.username');
        $this->apiID = constants('monty_sms_api.api_id');
        $this->url = constants('monty_sms_api.url');
        $this->debug = constants('monty_sms_api.debug_mode');
        $this->activeMode = constants('monty_sms_api.active_mode');

    }

    public function setExtraData(array $data)
    {
        $this->extraData = $data;
        return $this;
    }
    
    public function send($phone, $msg)
    {
        // checking the sms service is enable
        if(!$this->activeMode)
        {
            $errorData = [
                "status"=>false,
                "service_error_type"=>OTPService::$SMS_SERVICE_ERROR_TYPES[1],
                "message"=>'MONTY SMS SERVICE INACTIVE',
                "code"=>500,
            ];
            Log::info('MONTY SMS SERVICE INACTIVE: ', $errorData);
            return $errorData;
        }

        try {

            $param = [
                'username' => $this->username,
                'apiId' => $this->apiID,
                'destination' => $phone,
                'source' => $this->sender,
                'text' => $msg,
                'json' => 'True',
            ];

            $queryString = http_build_query($param);
            $apiUrl = $this->url. '?' .$queryString;
            $client = new \GuzzleHttp\Client();
            $request = $client->get($apiUrl);
            $responseCurl = json_decode($request->getBody()->getContents(),true);
            $status = (isset($responseCurl['ErrorCode']) && $responseCurl['ErrorCode'] == 0 ) ? true : false ;
            $response = [
                'status' => $status,
                'service_error_type'=> ($status)? OTPService::$SMS_SERVICE_ERROR_TYPES[0]:OTPService::$SMS_SERVICE_ERROR_TYPES[2],
            ];

            if($this->debug){
                Log::info('MONTY api url DEBUG: ', ['Response' => $apiUrl]);
                Log::info('MONTY env DEBUG: ', ['Response' => $param]);
                Log::info('MONTY raw response DEBUG: ', ['Response' => $responseCurl]);
                Log::info('MONTY SMS API DEBUG: ', ['Response' => $response]);
            }

            return $response;

        } catch (\Exception $e) {

            $errorData = [
                "status"=>false,
                "service_error_type"=>OTPService::$SMS_SERVICE_ERROR_TYPES[3],
                "message"=>$e->getMessage(),
                "code"=> $e->getCode(),
                "file"=> $e->getFile(),
                "line"=>$e->getLine()
            ];
            Log::info('MONTY SMS API CATCH: ', $errorData);
            return $errorData;

        }
    }

}
