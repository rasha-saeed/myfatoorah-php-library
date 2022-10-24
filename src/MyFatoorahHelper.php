<?php

namespace MyFatoorah\Library;

use Exception;

/**
 * Trait MyFatoorah is responsible for helping calling MyFatoorah API endpoints.
 */
class MyFatoorahHelper {
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
    public static function getPhone($inputString) {

        //remove any arabic digit
        $newNumbers = range(0, 9);

        $persianDecimal = ['&#1776;', '&#1777;', '&#1778;', '&#1779;', '&#1780;', '&#1781;', '&#1782;', '&#1783;', '&#1784;', '&#1785;']; // 1. Persian HTML decimal
        $arabicDecimal  = ['&#1632;', '&#1633;', '&#1634;', '&#1635;', '&#1636;', '&#1637;', '&#1638;', '&#1639;', '&#1640;', '&#1641;']; // 2. Arabic HTML decimal
        $arabic         = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩']; // 3. Arabic Numeric
        $persian        = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹']; // 4. Persian Numeric

        $string0 = str_replace($persianDecimal, $newNumbers, $inputString);
        $string1 = str_replace($arabicDecimal, $newNumbers, $string0);
        $string2 = str_replace($arabic, $newNumbers, $string1);
        $string3 = str_replace($persian, $newNumbers, $string2);

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
     * Get the rate that will convert the given weight unit to MyFatoorah default weight unit.
     * Weight must be in kg, g, lbs, or oz. Default is kg.
     *
     * @param string $unit It is the weight unit used.
     *
     * @return double|integer The conversion rate that will convert the given unit into the kg.
     *
     * @throws Exception Throw exception if the input unit is not support.
     */
    public static function getWeightRate($unit) {

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
                return (float) $rate;
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
     * @return double|integer   The conversion rate that will convert the given unit into the cm.
     *
     * @throws Exception        Throw exception if the input unit is not support.
     */
    public static function getDimensionRate($unit) {

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
                return (float) $rate;
            }
        }
        throw new Exception('Dimension units must be in cm, m, mm, in, or yd. Default is cm');
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Validate webhook signature function
     *
     * @param array  $dataArray webhook request array
     * @param string $secret    webhook secret key
     * @param string $signature MyFatoorah signature
     * @param int    $eventType MyFatoorah Event type Number (1, 2, 3 , 4)
     *
     * @return boolean
     */
    public static function isSignatureValid($dataArray, $secret, $signature, $eventType = 0) {

        if ($eventType == 2) {
            unset($dataArray['GatewayReference']);
        }

        uksort($dataArray, 'strcasecmp');

        $output = implode(',', array_map(
                        function ($v, $k) {
                            return sprintf("%s=%s", $k, $v);
                        },
                        $dataArray,
                        array_keys($dataArray)
        ));

        // generate hash of $field string
        $hash = base64_encode(hash_hmac('sha256', $output, $secret, true));

        if ($signature === $hash) {
            return true;
        } else {
            return false;
        }
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------
}
