<?php

namespace MyFatoorah\Library;

use Exception;

/**
 * Class MyfatoorahApiV2 is responsible for handling calling MyFatoorah API endpoints.
 * Also, It has necessary library functions that help in providing the correct parameters used endpoints.
 *
 * MyFatoorah offers a seamless business experience by offering a technology put together by our tech team. This enables smooth business operations involving sales activity, product invoicing, shipping, and payment processing. MyFatoorah invoicing and payment gateway solution trigger your business to greater success at all levels in the new age world of commerce. Leverage your sales and payments at all e-commerce platforms (ERPs, CRMs, CMSs) with transparent and slick applications that are well-integrated into social media and telecom services. For every closing sale click, you make a business function gets done for you, along with generating factual reports and statistics to fine-tune your business plan with no-barrier low-cost.
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
class MyfatoorahApiV2
{
    use TraitHelper;

    //-----------------------------------------------------------------------------------------------------------------------------------------

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
     * This is the file name or the logger object
     * It is used in logging the payment/shipping events to help in debugging and monitor the process and connections.
     *
     * @var string|object
     */
    protected $loggerObj;

    /**
     * If $loggerObj is set as a logger object, you should set this var with the function name that will be used in the debugging.
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
     * @param string        $countryMode Select the country mode.
     * @param boolean       $isTest      Set it to false for live mode.
     * @param string|object $loggerObj   This is the file name or the logger object. It is used in logging the payment/shipping events to help in debugging and monitor the process and connections. Leave it null, if you don't want to log the events.
     * @param string        $loggerFunc  If $loggerObj is set as a logger object, you should set this var with the function name that will be used in the debugging.
     */
    public function __construct($apiKey, $countryMode = 'KWT', $isTest = false, $loggerObj = null, $loggerFunc = null)
    {

        $mfCountries = $this->getMyFatoorahCountries();

        $code = strtoupper($countryMode);
        if (isset($mfCountries[$code])) {
            $this->apiURL = ($isTest) ? $mfCountries[$code]['testv2'] : $mfCountries[$code]['v2'];
        } else {
            $this->apiURL = ($isTest) ? 'https://apitest.myfatoorah.com' : 'https://api.myfatoorah.com';
        }

        $this->apiKey     = trim($apiKey);
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
     * @return object       The response object as the result of a successful calling to the API.
     *
     * @throws Exception    Throw exception if there is any curl/validation error in the MyFatoorah API endpoint URL
     */
    public function callAPI($url, $postFields = null, $orderId = null, $function = null)
    {

        //to prevent json_encode adding lots of decimal digits
        ini_set('precision', 14);
        ini_set('serialize_precision', -1);

        $request = isset($postFields) ? 'POST' : 'GET';
        $fields  = json_encode($postFields);

        $msgLog = "Order #$orderId ----- $function";

        if ($function != 'Direct Payment') {
            $this->log("$msgLog - Request: $fields");
        }

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
        if ($err && !is_string($res)) {
            $this->log("$msgLog - cURL Error: $err");
            throw new Exception($err);
        }

        $this->log("$msgLog - Response: $res");

        $json = json_decode($res);

        //***************************************
        //check for errors
        //***************************************

        $error = $this->getAPIError($json, $res);
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
    protected function getAPIError($json, $res)
    {

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
        $err = $this->getJsonErrors($json);
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
    protected function getJsonErrors($json)
    {

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
     * It will log the payment/shipping process events
     *
     * @param string $msg It is the string message that will be written in the log file
     *
     * @return null
     */
    public function log($msg)
    {

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

    /**
     * Gets the rate of a given currency according to the default currency of the MyFatoorah portal account.
     *
     * @param string $currency The currency that will be converted into the currency of MyFatoorah portal account.
     *
     * @return string       The conversion rate converts a given currency to the MyFatoorah account default currency.
     *
     * @throws Exception    Throw exception if the input currency is not support by MyFatoorah portal account.
     */
    public function getCurrencyRate($currency)
    {

        $json = $this->getCurrencyRates();
        foreach ($json as $value) {
            if ($value->Text == $currency) {
                return $value->Value;
            }
        }
        throw new Exception('The selected currency is not supported by MyFatoorah');
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get list of MyFatoorah currency rates
     *
     * @return object
     */
    public function getCurrencyRates()
    {

        $url = "$this->apiURL/v2/GetCurrenciesExchangeList";
        return $this->callAPI($url, null, null, 'Get Currencies Exchange List');
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Calculate the amount value that will be paid in each gateway
     *
     * @param double|integer $totalAmount
     * @param string         $currency
     * @param string         $paymentCurrencyIso
     * @param object         $allRatesData
     *
     * @return array
     */
    protected function calcGatewayData($totalAmount, $currency, $paymentCurrencyIso, $allRatesData)
    {

        //if ($currency != $paymentCurrencyIso) {
        foreach ($allRatesData as $data) {
            if ($data->Text == $currency) {
                $baseCurrencyRate = $data->Value;
            }
            if ($data->Text == $paymentCurrencyIso) {
                $gatewayCurrencyRate = $data->Value;
            }
        }

        if (isset($baseCurrencyRate) && isset($gatewayCurrencyRate)) {
            $baseAmount = ceil(((int) ($totalAmount * 1000)) / $baseCurrencyRate / 10) / 100;

            return [
                'GatewayTotalAmount' => round(($baseAmount * $gatewayCurrencyRate), 3),
                'GatewayCurrency'    => $paymentCurrencyIso
            ];
        } else {
            return [
                'GatewayTotalAmount' => $totalAmount,
                'GatewayCurrency'    => $currency
            ];
        }

        //        }
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     *
     * @param string $cachedFile
     *
     * @return array
     */
    protected static function createNewMFConfigFile($cachedFile)
    {

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
}
