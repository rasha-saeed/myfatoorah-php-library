<?php

namespace MyFatoorah\Library;

trait TraitHelper {

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
        } else {
            return [
                '',
                $string4
            ];
        }
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get the rate that will convert the given weight unit to MyFatoorah default weight unit.
     * 
     * @param string $unit It is the weight unit used. Weight must be in kg, g, lbs, or oz. Default is kg.
     * 
     * @return double|integer The conversion rate that will convert the given unit into the kg. 
     * 
     * @throws Exception Throw exception if the input unit is not support. Weight must be in kg, g, lbs, or oz. Default is kg.
     */
    public static function getWeightRate($unit) {

        $unit1 = strtolower($unit);
        if ($unit1 == 'kg' || $unit1 == 'kgs' || $unit1 == 'كج' || $unit1 == 'كلغ' || $unit1 == 'كيلو جرام' || $unit1 == 'كيلو غرام') {
            $rate = 1; //kg is the default
        } else if ($unit1 == 'g' || $unit1 == 'جرام' || $unit1 == 'غرام' || $unit1 == 'جم') {
            $rate = 0.001;
        } else if ($unit1 == 'lbs' || $unit1 == 'lb' || $unit1 == 'رطل' || $unit1 == 'باوند') {
            $rate = 0.453592;
        } else if ($unit1 == 'oz' || $unit1 == 'اوقية' || $unit1 == 'أوقية') {
            $rate = 0.0283495;
        } else {
            throw new Exception('Weight units must be in kg, g, lbs, or oz. Default is kg');
        }

        return $rate;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get the rate that will convert the given dimension unit to MyFatoorah default dimension unit.
     * 
     * @param string $unit It is the dimension unit used in width, hight, or depth. Dimension must be in cm, m, mm, in, or yd. Default is cm.
     * 
     * @return double|integer         The conversion rate that will convert the given unit into the cm.
     * 
     * @throws Exception    Throw exception if the input unit is not support. Dimension must be in cm, m, mm, in, or yd. Default is cm.
     */
    public static function getDimensionRate($unit) {

        $unit1 = strtolower($unit);
        if ($unit1 == 'cm' || $unit1 == 'سم') {
            $rate = 1; //cm is the default
        } elseif ($unit1 == 'm' || $unit1 == 'متر' || $unit1 == 'م') {
            $rate = 100;
        } else if ($unit1 == 'mm' || $unit1 == 'مم') {
            $rate = 0.1;
        } else if ($unit1 == 'in' || $unit1 == 'انش' || $unit1 == 'إنش' || $unit1 == 'بوصه' || $unit1 == 'بوصة') {
            $rate = 2.54;
        } else if ($unit1 == 'yd' || $unit1 == 'يارده' || $unit1 == 'ياردة') {
            $rate = 91.44;
        } else {
            throw new Exception('Dimension units must be in cm, m, mm, in, or yd. Default is cm');
        }

        return $rate;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Validate webhook signature function
     * 
     * @param array  $dataArray webhook request array
     * @param string $secret    webhook secret key
     * @param string $signature MyFatoorah signature
     * @param int    $eventType
     * 
     * @return boolean
     */
    public static function isSignatureValid($dataArray, $secret, $signature, $eventType = 0) {

        if ($eventType == 2) {
            unset($dataArray['GatewayReference']);
        }

        uksort($dataArray, 'strcasecmp');

        // uksort($data, function ($a, $b) {
        //   $a = mb_strtolower($a);
        //   $b = mb_strtolower($b);
        //   return strcmp($a, $b);
        // });

        $output = implode(',', array_map(
                        function ($v, $k) {
                    return sprintf("%s=%s", $k, $v);
                },
                        $dataArray,
                        array_keys($dataArray)
        ));

        //        $data      = utf8_encode($output);
        //        $keySecret = utf8_encode($secret);
        // generate hash of $field string 
        $hash = base64_encode(hash_hmac('sha256', $output, $secret, true));

        if ($signature === $hash) {
            return true;
        } else {
            return false;
        }
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get a list of MyFatoorah countries and their API URLs and names
     * 
     * @return array of MyFatoorah data
     */
    public static function getMyFatoorahCountries() {

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
}
