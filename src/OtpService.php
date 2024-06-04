<?php

namespace Savyour\SmsAndEmailPackage;


use Carbon\Carbon;
use http\Message;
use Illuminate\Support\Facades\Log;

class OtpService
{
    protected $length;
    protected $onlyDigits;
    protected $validity;
    protected $maxAttempts;
    protected $optMessage;
    protected $sendingSmsServiceName;
    private $currentDate;
    private $token;
    private $OTPData;
    private $message;
    private $OTPModel;
    private $serviceResponse;
    private $serviceBasePath;
    private $serviceBaseDir;
    private $debugMode;
    private $extraData = [];

    private static $SMS_SERVICE_ERROR_TYPES =[];
    private static $OTP_SERVICE_ERROR = [];
    public static $SMS_SERVICE_ERROR_TYPES_BK =[
        0=>"SUCCESS",
        1=>"NO_SERVICE_CALLED",
        2=>"ERROR_FROM_SERVICE",
        3=>"ERROR_IN_CATCH_BLOCK",
    ];
    /**
     * you can inject sms service class names for callback if one service is failed
     */
    protected $availableSmsServices= [];
    protected $sendFrom;

    public function __construct()
    {
        $this->debugMode = config('config-sms-and-email-package-service.otp.otp_debug_mode');
        $this->length = config('config-sms-and-email-package-service.otp.length');
        $this->onlyDigits = config('config-sms-and-email-package-service.otp.only_digits');
        $this->validity = config('config-sms-and-email-package-service.otp.validity');
        $this->maxAttempts = config('config-sms-and-email-package-service.otp.max_attempts');
        $this->optMessage = config('config-sms-and-email-package-service.otp.otp_message');
        $this->currentDate = date('Y-m-d');
        $this->OTPModel =  config('config-sms-and-email-package-service.OTPModelClass');
        $this->sendFrom = config('config-sms-and-email-package-service.otp.default_send_type');
//        $this->serviceBasePath = 'App/'.config('config-sms-and-email-package-service.wrapper_creation_path').'/Sms';
        $this->serviceBasePath = 'Savyour\SmsAndEmailPackage\ServicesWrappers';
        self::$SMS_SERVICE_ERROR_TYPES = config('config-sms-and-email-package-service.errors.service_wrapper_errors');
        self::$OTP_SERVICE_ERROR = config('config-sms-and-email-package-service.errors.opt_service_errors');
        $this->sendingSmsServiceName = self::$SMS_SERVICE_ERROR_TYPES['NO_SERVICE_CALLED'];
        $this->serviceBaseDir = __DIR__;

    }


    public function setSmsServices(array $smsServices)
    {
        if($this->debugMode)
        {
            Log::debug('setSmsServices available services ',$smsServices);
        }

        $this->availableSmsServices = $smsServices;
        return $this;
    }

    public function setExtraData(array $data)
    {
        if($this->debugMode)
        {
            Log::debug('setExtraData for service ',$data);
        }
        
        $this->extraData = $data;
        
        return $this;
    }

    public function setSendFrom(string $sendFrom)
    {
        if($this->debugMode)
        {
            Log::debug('Send from ' . $sendFrom);
        }

        $this->sendFrom = $sendFrom;
        return $this;
    }

    public function setOtpMessage(string $msg)
    {
        if($this->debugMode)
        {
            Log::debug('Send msg '.$msg);
        }

        $this->optMessage = $msg;
        return $this;
    }

    public function sendOtp(string $user_id, string $mobile_number,$isRetry = false)
    {

        if($this->debugMode)
        {
            Log::debug('sendOtp fn call ',[
                'user_id' => $user_id,
                'mobile_number' => $mobile_number,
                'isRetry' => $isRetry
            ]);
        }

        // checkin there is any service injected for send otp
        if(count($this->availableSmsServices) <= 0)
        {
            return [
                'status' => false,
                'message' => self::$OTP_SERVICE_ERROR['no_service_available'],
                'data'=> ['is_limit_reached' => false,]
            ];
        }

        // creating OTP code
        $this->createRandomToken();

        // getting user old otp data if exist
        $this->OTPData = $this->OTPModel::where('user_id', $user_id)
            ->where('mobile_number',$mobile_number)
            ->whereBetween('created_at',[$this->currentDate.' 00:00:00',$this->currentDate.' 23:59:59'])
            ->where('is_verified',0)
            ->where('type', $this->sendFrom)
            ->latest()
            ->first();
        // checking user validation attempts
        if(!empty($this->OTPData) && $this->OTPData->validation_attempts >= $this->maxAttempts) {
            return [
                'status' => false,
                'message' => self::$OTP_SERVICE_ERROR['validation_attempts_error_message'],
                'data'=> ['is_limit_reached' => true]
            ];
        }



        // checking is retry then reverse array
        if($isRetry)
        {
            // shift array to reverse for make
            krsort($this->availableSmsServices);
        }

        // checking if user opt not exist then send otp directly
        if(empty($this->OTPData) || $this->isExpired())
        {

            $this->OTPData = $this->OTPModel::create([
                'token' => $this->token,
                'user_id' => $user_id,
                'mobile_number' => $mobile_number,
                'validity' => $this->validity,
                'sending_attempts' => 0,
                'validation_attempts' => 0,
                'type' => $this->sendFrom,
                'is_verified'=> 0,
                'is_expired'=> 0,
                'generated_at' => date('Y-m-d H:i:s'),
            ]);

        }

        // now checking the user did not attempt
        if ($this->OTPData->sending_attempts >= $this->maxAttempts) {

            $message = str_replace('{duration}',$this->validity,self::$OTP_SERVICE_ERROR['sending_attempts_error_message']);
            return [
                'status' => false,
                'message' => $message,
                'data'=> [ 'is_limit_reached' => true]

            ];
        }

        // saving data
        $this->OTPData->token = $this->token;
        $this->OTPData->increment('sending_attempts');
        $this->OTPData->save();

        // sending sms
        $this->serviceResponse = $this->sendSMS();

        return $this->createResponse();
    }

    public function verifyOTP($user_id, $mobile_number, $token)
    {

        $this->OTPData = $this->OTPModel::where('user_id', $user_id)
            ->where('mobile_number',$mobile_number)
            ->whereBetween('generated_at',[$this->currentDate.' 00:00:00',$this->currentDate.' 23:59:59'])
            ->where('is_verified',0)
            ->orderBy('generated_at','DESC')
            ->first();

        // if record not found
        if (empty($this->OTPData)) {

            return [
                'status' => false,
                'message' => self::$OTP_SERVICE_ERROR['otp_not_found'],
                'data'=> [ 'is_limit_reached' => false]

            ];

        }

        // checking user validation attempts
        if ($this->OTPData->validation_attempts >= $this->maxAttempts) {
            return [
                'status' => false,
                'message' => self::$OTP_SERVICE_ERROR['validation_attempts_error_message'],
                'data'=> ['is_limit_reached' => true]
            ];
        }

        // checking is OTP is expired
        if ($this->isExpired()) {

            return [
                'status' => false,
                'message' => self::$OTP_SERVICE_ERROR['otp_expired'],
                'data'=> ['is_limit_reached' => false]
            ];
        }

        $this->OTPData->increment('validation_attempts');

        // mark otp verified
        if ($this->OTPData->token == $token)
        {
            $this->OTPData->sending_attempts = $this->maxAttempts;
            $this->OTPData->validation_attempts = $this->maxAttempts;
            $this->OTPData->is_expired = true;
            $this->OTPData->is_verified = true;
            // save data
            $this->OTPData->save();

            return [
                'status' => true,
                'message' => "OTP is valid.",
                'data'=> [ 'is_limit_reached' => false]
            ];
        }

        return [
            'status' => false,
            'message' => self::$OTP_SERVICE_ERROR['wrong_otp'],
            'data'=> ['is_limit_reached' => false]

        ];
    }

    private function createResponse()
    {
        $response = [
            'status'=>false,
            'message'=>self::$OTP_SERVICE_ERROR['all_service_failure'],
            'data'=> [
                'is_limit_reached'=>false,
                "service_response"=>$this->serviceResponse,
                'service_name' => $this->sendingSmsServiceName,
            ]
        ];

        // creating response
        if(isset($this->serviceResponse['status']) && $this->serviceResponse['status'] )
        {
            $response['status'] = true;
            $response['message'] = "OTP Send Successfully";
        }
        return $response;
    }

    private function createMessage()
    {
        $this->message = str_replace("{TOKEN}",$this->token,$this->optMessage);
    }

    private function sendSMS()
    {
        $response = [
            "status"=>false,
            "message"=>"no service called",
            'data'=> [
                'is_limit_reached' => false,
                "service_error_type"=>self::$SMS_SERVICE_ERROR_TYPES['NO_SERVICE_CALLED']
            ]
        ];

        // create message
        $this->createMessage();
        foreach($this->availableSmsServices as $smsService)
        {
            $smsServicePath = $this->serviceBasePath.'\\'.$smsService;
            $smsServicePath2 = $smsService.'.php';
            // checking file exist
            $fileExists = file_exists($this->serviceBaseDir.'/ServicesWrappers/'.$smsServicePath2);
            // skip if file dose not exist
            if(!$fileExists)
            {
                continue;
            }
            $smsServiceClass = new  $smsServicePath();
            $response = $smsServiceClass->send($this->OTPData->mobile_number,$this->message);
            // if message send then break
            if($response['status'])
            {
                // setting sending sms service class name to response
                $this->sendingSmsServiceName = str_replace("Service","",$smsService);
                break;
            }
        }

        return $response;

    }

    private function createRandomToken()
    {
        if ($this->onlyDigits) {
            $characters = '0123456789';
        } else {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        $length = strlen($characters);
        $token = '';
        for ($i = 0; $i < $this->length; $i++) {
            $token .= $characters[rand(0, $length - 1)];
        }

        $this->token =$token;

    }

    protected function isExpired()
    {

        if ($this->OTPData->is_expired) {
            return true;
        }

        $generatedTime = $this->OTPData->created_at->addMinutes($this->OTPData->validity);

        if (strtotime($generatedTime) >= strtotime(Carbon::now()->toDateTimeString())) {
            return false;
        }

        $this->OTPData->is_expired = true;
        $this->OTPData->save();

        return true;
    }

    public function checkBypass($number = null,$code = null, array $otpBypassOptions = [])
    {
        $canBypass = false;
        // checking the bypass argument
        if(!isset($otpBypassOptions['debug_otp_number']) || !isset($otpBypassOptions['debug_otp_code']))
        {
            return $canBypass;
        }

        if(count($otpBypassOptions) > 0)
        {
            $bypassNumber = preg_replace('/\s+/', '', $otpBypassOptions['debug_otp_number']);
            $bypassCodes = explode(',',preg_replace('/\s+/', '', $otpBypassOptions['debug_otp_code']));
            if($bypassNumber == $number)
            {
                $canBypass = true;
            }

            if($bypassNumber == $number && !empty($code) && in_array($code, $bypassCodes) )
            {
                $canBypass = true;
            }
            elseif(!empty($code))
            {
                $canBypass = false;
            }

        }

        return $canBypass;

    }

    public function sendMessage($number,$message)
    {   
        if($this->debugMode)
        {
            Log::debug('sendMessage called ',['$number'=>$number,'$message'=>$message]);
        }

        $response = [
            "status"=>false,
            "message"=>"no service called",
            "service_error_type" =>self::$SMS_SERVICE_ERROR_TYPES['NO_SERVICE_CALLED'],
            'data'=> [
                'is_limit_reached' => false,
                "service_error_type"=>self::$SMS_SERVICE_ERROR_TYPES['NO_SERVICE_CALLED']
            ]
        ];

        foreach($this->availableSmsServices as $smsService)
        {
            $smsServicePath = $this->serviceBasePath.'\\'.$smsService;
            $smsServicePath2 = $smsService.'.php';
            // checking file exist
            $fileExists = file_exists($this->serviceBaseDir.'/ServicesWrappers/'.$smsServicePath2);
            // skip if file dose not exist
            if(!$fileExists)
            {
                continue;
            }
            $smsServiceClass = new  $smsServicePath();
            $response = $smsServiceClass->setExtraData($this->extraData)->send($number,$message);
            // if message send then break
            if($response['status'])
            {
                // setting sending sms service class name to response
                $this->sendingSmsServiceName = str_replace("Service","",$smsService);
                break;
            }
        }

        return $response;

    }
}