<?php


namespace Savyour\SmsAndEmailPackage\FilesBlueprints;


class SmsWraperBlueprint
{

    public static function getFileContent(){


        return ' <?php 

namespace App\{FilePath};

use Illuminate\Support\Facades\Log;

class {ClassName} implements SmsWrapperInterface {

    private $debug;
    private $serviceErrorMessage;
    
    public function __construct()
    {
        $this->debug = true; // you can make this dynamic by adding key in env or create a constant file and fetch there;
        $this->serviceErrorMessage =  config("config-sms-and-email-package-service.errors.service_wrapper_errors");
    }
    
    /***
    * This method is empty you can define your api logic here 
    * 
    * $response = ["status" => $status,"service_error_type"=> ($status)? OTPService::$SMS_SERVICE_ERROR_TYPES[0]:OTPService::$SMS_SERVICE_ERROR_TYPES[2],
            ];
    *
    *
    ***/
    public function send($phone, $msg)
    {
         try {
             
         } catch (\Exception $e) {

            $errorData = [
                "status"=>false,
                "service_error_type"=>$this->serviceErrorMessage["ERROR_IN_CATCH_BLOCK"],
                "message"=>$e->getMessage(),
                "code"=> $e->getCode(),
                "file"=> $e->getFile(),
                "line"=>$e->getLine()
            ];
            Log::info(" {ClassName} SMS API CATCH: ", $errorData);
            return $errorData;

        }
    }
    
}

';
    }
    public static function getInterFaceContent(){


        return ' <?php

namespace App\{FilePath};


interface SmsWrapperInterface
{
    /**
     * method for send sms you can override this logic
     *
     * @return ["status" => true|false] array
     *
     * return must be return type array with status key to check the sms service status
     */
    public function send($phone, $msg);
}';
    }

}