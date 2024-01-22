<?php

namespace Savyour\SmsAndEmailPackage\ServicesWrappers;

class ConvexService
{
    private $sender, $apikey, $apiSecret, $url, $debug,$activeMode;

    public function __construct()
    {
        // monty_sms_api
        $this->sender = constants('convex_sms_api.sender');
        $this->apikey = constants('convex_sms_api.apikey');
        $this->apiSecret = constants('convex_sms_api.secretkey');
        $this->url = constants('convex_sms_api.url');
        $this->debug = constants('convex_sms_api.debug_mode');
        $this->activeMode = constants('convex_sms_api.active_mode');

    }

    public function send($phone, $msg)
    {
        // checking the sms service is enable
        if(!$this->activeMode)
        {
            $errorData = [
                "status"=>false,
                "service_error_type"=>OTPService::$SMS_SERVICE_ERROR_TYPES[1],
                "message"=>'CONVEX SMS SERVICE INACTIVE',
                "code"=>500,
            ];
            Log::info('CONVEX SMS SERVICE INACTIVE: ', $errorData);
            return $errorData;
        }

        try {


            // removing plus sign from number
            $phone = str_replace('+','',$phone);
            $param = [
                'apikey' => $this->apikey,
                'apisecret' => $this->apiSecret,
                'from' => $this->sender,
                'to' => $phone,
                'message' => $msg,
                'response_type' => 'json',
            ];

            $queryString = http_build_query($param);

            $apiUrl = $this->url. '?' .$queryString;
            $client = new \GuzzleHttp\Client();
            $request = $client->get($apiUrl);
            $responseCurl = json_decode($request->getBody()->getContents(),true);
            $status = (isset($responseCurl['response']) && $responseCurl['response'] == 300 ) ? true : false ;
            $response = [
                'status' => $status,
                'service_error_type'=> ($status)? OTPService::$SMS_SERVICE_ERROR_TYPES[0]:OTPService::$SMS_SERVICE_ERROR_TYPES[2],
            ];

            if($this->debug){
                Log::info('CONVEX SMS API DEBUG: ', ['Response' => $response]);
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
            Log::info('CONVEX SMS API CATCH: ', $errorData);
            return $errorData;

        }
    }

}
