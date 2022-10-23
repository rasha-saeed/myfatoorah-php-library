<?php

namespace MyFatoorah\Library;

use Exception;

/**
 * MyFatoorah is responsible for handling calling MyFatoorah API endpoints.
 * Also, It has necessary library functions that help in providing the correct parameters used endpoints.
 *
 * MyFatoorah offers a seamless business experience by offering a technology put together by our tech team. It enables smooth business operations involving sales activity, product invoicing, shipping, and payment processing. MyFatoorah invoicing and payment gateway solution trigger your business to greater success at all levels in the new age world of commerce. Leverage your sales and payments at all e-commerce platforms (ERPs, CRMs, CMSs) with transparent and slick applications that are well-integrated into social media and telecom services. For every closing sale click, you make a business function gets done for you, along with generating factual reports and statistics to fine-tune your business plan with no-barrier low-cost.
 * Our technology experts have designed the best GCC E-commerce solutions for the native financial instruments (Debit Cards, Credit Cards, etc.) supporting online sales and payments, for events, shopping, mall, and associated services.
 *
 * Created by MyFatoorah http://www.myfatoorah.com/
 * Developed By tech@myfatoorah.com
 * Date: 03/03/2021
 * Time: 12:00
 *
 * API Documentation on https://myfatoorah.readme.io/docs
 * Library Documentation and Download link on https://myfatoorah.readme.io/docs/php-library
 *
 * @author    MyFatoorah <tech@myfatoorah.com>
 * @copyright 2021 MyFatoorah, All rights reserved
 * @license   GNU General Public License v3.0
 */
Class MyFatoorah {

    use MyFatoorahTrait;
//    use MyFatoorahPayment;
//    use MyFatoorahPaymentStatus;
//    use MyFatoorahShipping;
//    use MyFatoorahRefund;
//    use MyFatoorahList;

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * The configuration used to connect to MyFatoorah test/live API server
     *
     * @var array
     */
    protected $config = '';
    
    /**
     * The URL used to connect to MyFatoorah test/live API server
     *
     * @var string
     */
    protected $apiURL = '';

    /**
     * The API Token Key is the authentication which identify a user that is using the app
     * To generate one follow instruction here https://myfatoorah.readme.io/docs/live-token
     *
     * @var string
     */
    protected $apiKey;

    /**
     * The file name or the logger object
     * It is used in logging the payment/shipping events to help in debugging and monitor the process and connections.
     *
     * @var string|object
     */
    protected $loggerObj;

    /**
     * If $loggerObj is set as a logger object, you should set $loggerFunc with the function name that will be used in the debugging.
     *
     * @var string
     */
    protected $loggerFunc;

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Constructor
     * Initiate new MyFatoorah API process
     *
     * @param string        $apiKey      The API Token Key is the authentication which identify a user that is using the app. To generate one follow instruction here https://myfatoorah.readme.io/docs/live-token.
     * @param string        $countryCode Select the country mode.
     * @param boolean       $isTest      Set it to false for live mode.
     * @param string|object $loggerObj   The file name or the logger object. It is used in logging the payment/shipping events to help in debugging and monitor the process and connections. Leave it null, if you don't want to log the events.
     * @param string        $loggerFunc  If $loggerObj is set as a logger object, you should set $loggerFunc with the function name that will be used in the debugging.
     */
    public function __construct($config, $loggerObj = null, $loggerFunc = null) {

        $mfConfig = self::getMFConfig();

        $code = strtoupper($config['countryCode']);
        if (isset($mfConfig[$code])) {
            $this->apiURL = ($config['isTest']) ? $mfConfig[$code]['testv2'] : $mfConfig[$code]['v2'];
        } else {
            $this->apiURL = ($config['isTest']) ? 'https://apitest.myfatoorah.com' : 'https://api.myfatoorah.com';
        }

        $config['apiKey'] = trim($config['apiKey']);
        $this->config     = $config;

        $this->apiKey     = $config['apiKey'];
        $this->loggerObj  = $loggerObj;
        $this->loggerFunc = $loggerFunc;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     *
     * @param string         $url        MyFatoorah API endpoint URL
     * @param array          $postFields POST request parameters array. It should be set to null if the request is GET.
     * @param integer|string $orderId    The order id or the payment id of the process, used for the events logging.
     * @param string         $function   The requester function name, used for the events logging.
     *
     * @return object|array       The response object as the result of a successful calling to the API.
     *
     * @throws Exception    Throw exception if there is any curl/validation error in the MyFatoorah API endpoint URL
     */
    public function callAPI($url, $postFields = null, $orderId = null, $function = null) {

        //to prevent json_encode adding lots of decimal digits
        ini_set('precision', 14);
        ini_set('serialize_precision', -1);

        $request = isset($postFields) ? 'POST' : 'GET';
        $fields  = json_encode($postFields);

        $msgLog = "Order #$orderId ----- $function";
        $this->log("$msgLog - Request: $fields");

        //***************************************
        //call url
        //***************************************
        $curl = curl_init($url);

        curl_setopt_array($curl, array(
            CURLOPT_CUSTOMREQUEST  => $request,
            CURLOPT_POSTFIELDS     => $fields,
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer $this->apiKey", 'Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true
        ));

        $res = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        //example set a local ip to host apitest.myfatoorah.com
        if ($err) {
            $this->log("$msgLog - cURL Error: $err");
            throw new Exception($err);
        }

        $this->log("$msgLog - Response: $res");

        $json = json_decode((string) $res);

        //***************************************
        //check for errors
        //***************************************

        $error = self::getAPIError($json, (string) $res);
        if ($error) {
            $this->log("$msgLog - Error: $error");
            throw new Exception($error);
        }

        //***************************************
        //Success
        //***************************************
        return $json;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Handles Endpoint Errors Function
     *
     * @param object|string $json
     * @param string        $res
     *
     * @return string
     */
    protected static function getAPIError($json, $res) {

        if (isset($json->IsSuccess) && $json->IsSuccess == true) {
            return '';
        }

        //to avoid blocked IP like:
        //<html>
        //<head><title>403 Forbidden</title></head>
        //<body>
        //<center><h1>403 Forbidden</h1></center><hr><center>Microsoft-Azure-Application-Gateway/v2</center>
        //</body>
        //</html>
        //and, skip apple register <YourDomainName> tag error
        $stripHtmlStr = strip_tags($res);
        if ($res != $stripHtmlStr && !stripos($stripHtmlStr, 'apple-developer-merchantid-domain-association')) {
            return trim(preg_replace('/\s+/', ' ', $stripHtmlStr));
        }

        //Check for the errors
        $err = self::getJsonErrors($json);
        if ($err) {
            return $err;
        }

        if (!$json) {
            return (!empty($res) ? $res : 'Kindly review your MyFatoorah admin configuration due to a wrong entry.');
        }

        if (is_string($json)) {
            return $json;
        }

        return '';
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Check for the json (response model) errors
     *
     * @param object|string $json
     *
     * @return string
     */
    protected static function getJsonErrors($json) {

        if (isset($json->ValidationErrors) || isset($json->FieldsErrors)) {
            //$err = implode(', ', array_column($json->ValidationErrors, 'Error'));

            $errorsObj = isset($json->ValidationErrors) ? $json->ValidationErrors : $json->FieldsErrors;
            $blogDatas = array_column($errorsObj, 'Error', 'Name');

            return implode(', ', array_map(function ($k, $v) {
                        return "$k: $v";
                    }, array_keys($blogDatas), array_values($blogDatas)));
        }

        if (isset($json->Data->ErrorMessage)) {
            return $json->Data->ErrorMessage;
        }

        //if not, get the message.
        //sometimes Error value of ValidationErrors is null, so either get the "Name" key or get the "Message"
        //example {
        //"IsSuccess":false,
        //"Message":"Invalid data",
        //"ValidationErrors":[{"Name":"invoiceCreate.InvoiceItems","Error":""}],
        //"Data":null
        //}
        //example {
        //"Message":
        //"No HTTP resource was found that matches the request URI 'https://apitest.myfatoorah.com/v2/SendPayment222'.",
        //"MessageDetail":
        //"No route providing a controller name was found to match request URI 'https://apitest.myfatoorah.com/v2/SendPayment222'"
        //}

        if (isset($json->Message)) {
            return $json->Message;
        }

        return '';
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get a list of MyFatoorah countries and their API URLs and names
     *
     * @return array of MyFatoorah data
     */
    private static function getMFConfig() {

        $cachedFile = dirname(__FILE__) . '/mf-config.json';

        if (file_exists($cachedFile)) {
            if ((time() - filemtime($cachedFile) > 3600)) {
                $countries = self::createNewMFConfigFile($cachedFile);
            }

            if (!empty($countries)) {
                return $countries;
            }

            $cache = file_get_contents($cachedFile);
            return ($cache) ? json_decode($cache, true) : [];
        } else {
            return self::createNewMFConfigFile($cachedFile);
        }
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     *
     * @param string $cachedFile
     *
     * @return array
     */
    private static function createNewMFConfigFile($cachedFile) {

        $curl = curl_init('https://portal.myfatoorah.com/Files/API/mf-config.json');
        curl_setopt_array($curl, array(
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true
        ));

        $response  = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($http_code == 200 && is_string($response)) {
            file_put_contents($cachedFile, $response);
            return json_decode($response, true);
        } elseif ($http_code == 403) {
            touch($cachedFile);
            $fileContent = file_get_contents($cachedFile);
            if (!empty($fileContent)) {
                return json_decode($fileContent, true);
            }
        }
        return [];
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * It will log the events
     *
     * @param string $msg It is the string message that will be written in the log file
     *
     * @return null
     */
    public function log($msg) {

        if (!$this->loggerObj) {
            return;
        }
        if (is_string($this->loggerObj)) {
            error_log(PHP_EOL . date('d.m.Y h:i:s') . ' - ' . $msg, 3, $this->loggerObj);
        } elseif (method_exists($this->loggerObj, $this->loggerFunc)) {
            $this->loggerObj->{$this->loggerFunc}($msg);
        }
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------
}