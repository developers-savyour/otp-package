<?php

namespace Savyour\SmsAndEmailPackage\ServicesWrappers;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client as GuzzleClient;

class CequensWhatsappService
{
    private $settings, $debug, $className, $settingsModelClass;
    public static $SERVICE_RESPONSE_CODE_LABELS = [];
    public static $OTP_SERVICE_ERROR = [];

    public function __construct()
    {
        $this->settingsModelClass =  config('config-sms-and-email-package-service.SettingsModelClass');
        $this->settings = config('config-sms-and-email-package-service.services_constants.cequens_whatsapp');
        // $this->eOceanToken = $this->settingsModelClass::get('eocean_auth_token');
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
                "message"=>$this->className.' WHATSAPP SERVICE INACTIVE ',
                "code"=>500,
            ];
            Log::info($this->className.'WHATSAPP SERVICE INACTIVE : status '.$this->settings['active_mode'], $errorData);
            return $errorData;
        }

        try {

            $url = $this->settings['url'];
            $namespace = $this->settings['namespace'];
            $templateName = $this->settings['template_name'];
            $token = $this->settingsModelClass::get('cequens_whatsapp_token');
            $cookie = $this->settingsModelClass::get('cequens_whatsapp_cookie');
            $number = str_replace('+', '', $phone);

            $data = array(
                "to" => $number,
                "recipient_type" => "individual",
                "type" => "template",
                "template" => array(
                    "name" => $templateName,
                    "namespace" => $namespace,
                    "components" => array(
                    array(
                        "type" => "body",
                        "parameters" => array(
                        array(
                            "type" => "text",
                            "text" => $msg
                        )
                        )
                    ),
                    array(
                        "type" => "button",
                        "sub_type" => "url",
                        "index" => "0",
                        "parameters" => array(
                        array(
                            "type" => "text",
                            "text" => $msg
                        )
                        )
                    )
                    ),
                    "language" => array(
                    "policy" => "deterministic",
                    "code" => "en"
                    )
                )
            );

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Cookie: ' . $cookie
            ),
            ));

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            // Decode the response JSON content
            $responseCurl = json_decode($response, true);

            curl_close($curl);

            // Check the response status
            $status = $httpCode == 200;
            $serviceMessage = isset($responseCurl['messages']) ? $responseCurl['messages'] : '';
            // Prepare the response data
            $response = [
                'status' => $status,
                'response' =>  ["raw"=>$responseCurl,"message"=>$serviceMessage],
                'service_error_type' => $status ? self::$OTP_SERVICE_ERROR['SUCCESS']:self::$OTP_SERVICE_ERROR['ERROR_FROM_SERVICE'],
            ];
            // Log debug information if debug mode is enabled
            if ($this->debug) {
                Log::info($this->className.' WHATSAPP API DEBUG: ', ['Response' => $response,'request'=>$data,'token'=> $token,"cookie" => $cookie]);
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
            Log::info($this->className.' WHATSAPP API CATCH: ', $errorData);

            return $errorData;
        }

    }

}
