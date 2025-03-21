<?php

namespace MyFatoorah\Library;

use Exception;

/**
 * Trait MyFatoorah is responsible for helping calling MyFatoorah API endpoints.
 */
class MyFatoorahHelper
{

    /**
     * The file name or the logger object
     * It is used in logging the payment/shipping events to help in debugging and monitor the process and connections.
     *
     * @var string|object
     */
    public static $loggerObj;

    /**
     * The function name that will be used in the debugging if $loggerObj is set as a logger object.
     *
     * @var string
     */
    public static $loggerFunc;

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Returns the country code and the phone after applying MyFatoorah restriction
     *
     * Matching regular expression pattern: ^(?:(\+)|(00)|(\\*)|())[0-9]{3,14}((\\#)|())$
     * if (!preg_match('/^(?:(\+)|(00)|(\\*)|())[0-9]{3,14}((\\#)|())$/iD', $inputString))
     * String length: inclusive between 0 and 11
     *
     * @param string $inputString It is the input phone number provide by the end user.
     *
     * @return array        That contains the phone code in the 1st element the the phone number the the 2nd element.
     *
     * @throws Exception    Throw exception if the input length is less than 3 chars or long than 14 chars.
     */
    public static function getPhone($inputString)
    {

        //remove any arabic digit
        $string3 = self::convertArabicDigitstoEnglish($inputString);

        //Keep Only digits
        $string4 = preg_replace('/[^0-9]/', '', $string3);

        //remove 00 at start
        if (strpos($string4, '00') === 0) {
            $string4 = substr($string4, 2);
        }

        if (!$string4) {
            return ['', ''];
        }

        //check for the allowed length
        $len = strlen($string4);
        if ($len < 3 || $len > 14) {
            throw new Exception('Phone Number lenght must be between 3 to 14 digits');
        }

        //get the phone arr
        if (strlen(substr($string4, 3)) > 3) {
            return [
                substr($string4, 0, 3),
                substr($string4, 3)
            ];
        }

        return [
            '',
            $string4
        ];
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Converts any Arabic or Persian numbers to English digits
     *
     * @param string $inputString It is the input phone number provide by the end user.
     *
     * @return string
     */
    protected static function convertArabicDigitstoEnglish($inputString)
    {

        $newNumbers = range(0, 9);

        $persianDecimal = ['&#1776;', '&#1777;', '&#1778;', '&#1779;', '&#1780;', '&#1781;', '&#1782;', '&#1783;', '&#1784;', '&#1785;']; // 1. Persian HTML decimal
        $arabicDecimal  = ['&#1632;', '&#1633;', '&#1634;', '&#1635;', '&#1636;', '&#1637;', '&#1638;', '&#1639;', '&#1640;', '&#1641;']; // 2. Arabic HTML decimal
        $arabic         = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩']; // 3. Arabic Numeric
        $persian        = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹']; // 4. Persian Numeric

        $string0 = str_replace($persianDecimal, $newNumbers, $inputString);
        $string1 = str_replace($arabicDecimal, $newNumbers, $string0);
        $string2 = str_replace($arabic, $newNumbers, $string1);

        return str_replace($persian, $newNumbers, $string2);
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get the rate that will convert the given weight unit to MyFatoorah default weight unit.
     * Weight must be in kg, g, lbs, or oz. Default is kg.
     *
     * @param string $unit It is the weight unit used.
     *
     * @return double|int The conversion rate that will convert the given unit into the kg.
     *
     * @throws Exception Throw exception if the input unit is not support.
     */
    public static function getWeightRate($unit)
    {

        $lUnit = strtolower($unit);

        //kg is the default
        $rateUnits = [
            '1'         => ['kg', 'kgs', 'كج', 'كلغ', 'كيلو جرام', 'كيلو غرام'],
            '0.001'     => ['g', 'جرام', 'غرام', 'جم'],
            '0.453592'  => ['lbs', 'lb', 'رطل', 'باوند'],
            '0.0283495' => ['oz', 'اوقية', 'أوقية'],
        ];

        foreach ($rateUnits as $rate => $unitArr) {
            if (array_search($lUnit, $unitArr) !== false) {
                return (double) $rate;
            }
        }
        throw new Exception('Weight units must be in kg, g, lbs, or oz. Default is kg');
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get the rate that will convert the given dimension unit to MyFatoorah default dimension unit.
     * Dimension must be in cm, m, mm, in, or yd. Default is cm.
     *
     * @param string $unit It is the dimension unit used in width, hight, or depth.
     *
     * @return double|int   The conversion rate that will convert the given unit into the cm.
     *
     * @throws Exception        Throw exception if the input unit is not support.
     */
    public static function getDimensionRate($unit)
    {

        $lUnit = strtolower($unit);

        //cm is the default
        $rateUnits = [
            '1'     => ['cm', 'سم'],
            '100'   => ['m', 'متر', 'م'],
            '0.1'   => ['mm', 'مم'],
            '2.54'  => ['in', 'انش', 'إنش', 'بوصه', 'بوصة'],
            '91.44' => ['yd', 'يارده', 'ياردة'],
        ];

        foreach ($rateUnits as $rate => $unitArr) {
            if (array_search($lUnit, $unitArr) !== false) {
                return (double) $rate;
            }
        }
        throw new Exception('Dimension units must be in cm, m, mm, in, or yd. Default is cm');
    }

    //-----------------------------------------------------------------------------------------------------------------------------
    public static function processWebhookRequest($secretKey, $request = null)
    {
        if (!$secretKey) {
            throw new Exception('Store needs to be configured.');
        }

        list($mfVersion, $signature) = self::getMfHeaders();

        if (!$request) {
            $body    = file_get_contents('php://input');
            $request = json_decode($body, true);
        }

        if (empty($request['Data'])) {
            throw new Exception('Wrong data.');
        }

        if (self::{"checkSignatureValidation$mfVersion"}($request, $secretKey, $signature)) {
            return $request;
        }
        throw new Exception('Validation error.');
    }

    private static function getMfHeaders()
    {
        $apache  = (array) apache_request_headers();
        $headers = array_change_key_case($apache);

        if (empty($headers['myfatoorah-signature']) || empty($headers['myfatoorah-webhook-version'])) {
            throw new Exception('Wrong request.');
        }

        $mfVersion = strtoupper($headers['myfatoorah-webhook-version']);
        if ($mfVersion != 'V1' && $mfVersion != 'V2') {
            throw new Exception('Wrong version.');
        }
        return [$mfVersion, $headers['myfatoorah-signature']];
    }

    protected static function checkSignatureValidationV1($request, $secretKey, $signature)
    {
        if (!isset($request['EventType']) || !isset($request['Event'])) {
            throw new Exception('Worng event.');
        }

        return MyFatoorah::isSignatureValid($request['Data'], $secretKey, $signature, $request['EventType']);
    }

    protected static function checkSignatureValidationV2($request, $secretKey, $signature)
    {
        if (!isset($request['Event']['Code']) || !isset($request['Event']['Name'])) {
            throw new Exception('Worng event.');
        }

        $dataModel = self::getV2DataModel($request['Event']['Code'], $request['Data']);
        return self::checkSignatureValidation($dataModel, $secretKey, $signature);
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Validate webhook version 1 signature function
     * keep it for the old system
     *
     * @param array  $dataModel Webhook request array
     * @param string $secretKey Webhook secret key
     * @param string $signature MyFatoorah signature
     * @param int    $eventType MyFatoorah Event type Number (1, 2, 3 , 4)
     *
     * @return boolean
     */
    public static function isSignatureValid($dataModel, $secretKey, $signature, $eventType = 1)
    {

        if ($eventType == 2) {
            unset($dataModel['GatewayReference']);
        }

        uksort($dataModel, 'strcasecmp');

        return self::checkSignatureValidation($dataModel, $secretKey, $signature);
    }

    private static function checkSignatureValidation($dataModel, $secretKey, $signature)
    {
        $mapFun = function ($v, $k) {
            return sprintf("%s=%s", $k, $v);
        };
        $outputArr = array_map($mapFun, $dataModel, array_keys($dataModel));
        $output    = implode(',', $outputArr);

        // generate hash of $field string
        $hash = base64_encode(hash_hmac('sha256', $output, $secretKey, true));

        return $signature === $hash;
    }

    private static function getV2DataModel($code, $data)
    {
        $dataModels = [
            1 => [
                'Invoice.Id'            => $data['Invoice']['Id'],
                'Invoice.Status'        => $data['Invoice']['Status'],
                'Transaction.Status'    => $data['Transaction']['Status'],
                'Transaction.PaymentId' => $data['Transaction']['PaymentId'],
                'Customer.Reference'    => $data['Customer']['Reference'],
            ],
            2 => [
                'Refund.Id'                  => $data['Refund']['Id'],
                'Refund.Status'              => $data['Refund']['Status'],
                'Amount.ValueInBaseCurrency' => $data['Amount']['ValueInBaseCurrency'],
                'ReferencedInvoice.Id'       => $data['ReferencedInvoice']['Id'],
            ],
            3 =>
            [
                'Deposit.Reference'            => $data['Deposit']['Reference'],
                'Deposit.ValueInBaseCurrency'  => $data['Deposit']['ValueInBaseCurrency'],
                'Deposit.NumberOfTransactions' => $data['Deposit']['NumberOfTransactions'],
            ],
            4 =>
            [
                'Supplier.Code'      => $data['Supplier']['Code'],
                'KycDecision.Status' => $data['KycDecision']['Status'],
            ],
            5 =>
            [
                'Recurring.Id'               => $data['Recurring']['Id'],
                'Recurring.Status'           => $data['Recurring']['Status'],
                'Recurring.InitialInvoiceId' => $data['Recurring']['InitialInvoiceId'],
            ]
        ];

        return $dataModels[$code] ?? null;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get a list of MyFatoorah countries, their API URLs, and names.
     *
     * @return array of MyFatoorah data
     */
    public static function getMFCountries()
    {

        $cachedFile = dirname(__FILE__) . '/mf-config.json';

        if (file_exists($cachedFile)) {
            if ((time() - filemtime($cachedFile) > 3600)) {
                $countries = self::getMFConfigFileContent($cachedFile);
            }

            if (!empty($countries)) {
                return $countries;
            }

            $cache = file_get_contents($cachedFile);
            return ($cache) ? json_decode($cache, true) : [];
        } else {
            return self::getMFConfigFileContent($cachedFile);
        }
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Cache a list of MyFatoorah countries, their API URLs, and names.
     *
     * @param string $cachedFile The file name used in caching data.
     *
     * @return array of MyFatoorah data
     */
    protected static function getMFConfigFileContent($cachedFile)
    {

        $curl = curl_init('https://portal.myfatoorah.com/Files/API/mf-config.json');

        $option = [
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true
        ];
        curl_setopt_array($curl, $option);

        $response  = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($http_code == 200 && is_string($response)) {
            $responseText = trim($response, '﻿'); //remove the hidden character between the single quotes
            file_put_contents($cachedFile, $responseText);
            return json_decode($responseText, true);
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
     * Filter an input from global variables like $_GET, $_POST, $_REQUEST, $_COOKIE, $_SERVER
     *
     * @param string $name The field name the need to be filter.
     * @param string $type The input type to be filter (GET, POST, REQUEST, COOKIE, SERVER).
     *
     * @return string|null
     */
    public static function filterInputField($name, $type = 'GET')
    {
        if (isset($GLOBALS["_$type"][$name])) {
            return htmlspecialchars($GLOBALS["_$type"][$name]);
        }
        return null;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get the payment status link
     *
     * @param string $url       The payment URL link
     * @param string $paymentId The payment Id
     *
     * @return string
     */
    public static function getPaymentStatusLink($url, $paymentId)
    {
        //to overcome session urls
        $pattern = '/MpgsAuthentication.*|ApplePayComplete.*|GooglePayComplete.*/i';
        return preg_replace($pattern, "Result?paymentId=$paymentId", $url);
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------
}
