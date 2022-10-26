<?php

namespace MyFatoorah\Library;

use MyFatoorah\Library\MyFatoorah;
use Exception;

/**
 *  MyFatoorahPayment handles the payment process of MyFatoorah API endpoints
 *
 * @author    MyFatoorah <tech@myfatoorah.com>
 * @copyright 2021 MyFatoorah, All rights reserved
 * @license   GNU General Public License v3.0
 */
class MyFatoorahPayment extends MyFatoorah
{

    /**
     * The file name used in caching the gateways data
     *
     * @var string
     */
    public static $pmCachedFile = __DIR__ . '/mf-methods.json';

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * List available Payment Gateways (POST API)
     *
     * @param double|integer $invoiceValue       Total invoice amount.
     * @param string         $displayCurrencyIso Total invoice currency.
     * @param boolean        $isCached           It used to cache the gateways
     *
     * @return array
     */
    public function initiatePayment($invoiceValue = 0, $displayCurrencyIso = '', $isCached = false)
    {

        $postFields = [
            'InvoiceAmount' => $invoiceValue,
            'CurrencyIso'   => $displayCurrencyIso,
        ];

        $json = $this->callAPI("$this->apiURL/v2/InitiatePayment", $postFields, null, 'Initiate Payment');

        $paymentMethods = isset($json->Data->PaymentMethods) ? $json->Data->PaymentMethods : [];

        if (!empty($paymentMethods) && $isCached) {
            file_put_contents(self::$pmCachedFile, json_encode($paymentMethods));
        }
        return $paymentMethods;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * List available Cached Payment Gateways
     *
     * @return array of Cached payment methods.
     */
    public function getCachedVendorGateways()
    {

        if (file_exists(self::$pmCachedFile)) {
            $cache = file_get_contents(self::$pmCachedFile);
            return ($cache) ? json_decode($cache) : [];
        } else {
            return $this->initiatePayment(0, '', true);
        }
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * List available cached  Payment Methods
     *
     * @param bool $isAppleRegistered Is site domain is registered with applePay and MyFatoorah or not
     *
     * @return array
     */
    public function getCachedPaymentMethodsArray($isAppleRegistered = false)
    {

        $gateways       = $this->getCachedVendorGateways();
        $paymentMethods = ['all' => [], 'cards' => [], 'form' => [], 'ap' => []];
        foreach ($gateways as $gateway) {
            $paymentMethods = $this->addGatewayToPaymentMethodsArray($gateway, $paymentMethods, $isAppleRegistered);
        }

        //add only one ap gateway
        $paymentMethods['ap'] = (isset($paymentMethods['ap'][0])) ? $paymentMethods['ap'][0] : [];

        return $paymentMethods;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Add the MyFatoorah gateway object to the a given Payment Methods Array
     *
     * @param object  $gateway           MyFatoorah gateway object.
     * @param array   $paymentMethods    Payment Methods Array.
     * @param boolean $isAppleRegistered Is site domain is registered with applePay and MyFatoorah or not.
     *
     * @return array
     */
    protected function addGatewayToPaymentMethodsArray($gateway, $paymentMethods, $isAppleRegistered)
    {

        if ($gateway->PaymentMethodCode == 'ap') {
            if ($isAppleRegistered) {
                $paymentMethods['ap'][] = $gateway;
            } else {
                $paymentMethods['cards'][] = $gateway;
            }
            $paymentMethods['all'][] = $gateway;
        } else {
            if ($gateway->IsEmbeddedSupported) {
                $paymentMethods['form'][] = $gateway;
                $paymentMethods['all'][]  = $gateway;
            } elseif (!$gateway->IsDirectPayment) {
                $paymentMethods['cards'][] = $gateway;
                $paymentMethods['all'][]   = $gateway;
            }
        }

        return $paymentMethods;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get Payment Method Object
     *
     * @param string         $gateway            MyFatoorah gateway object.
     * @param string         $gatewayType        The Search key     ['PaymentMethodId', 'PaymentMethodCode'].
     * @param double|integer $invoiceValue       Total invoice amount.
     * @param string         $displayCurrencyIso Total invoice currency.
     *
     * @return object
     *
     * @throws Exception
     */
    public function getOnePaymentMethod($gateway, $gatewayType = 'PaymentMethodId', $invoiceValue = 0, $displayCurrencyIso = '')
    {

        $paymentMethods = $this->initiatePayment($invoiceValue, $displayCurrencyIso);

        $pm = null;
        foreach ($paymentMethods as $method) {
            if ($method->$gatewayType == $gateway) {
                $pm = $method;
                break;
            }
        }

        if (!isset($pm)) {
            throw new Exception('Please contact Account Manager to enable the used payment method in your account');
        }

        return $pm;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get the invoice/payment URL and the invoice id
     *
     * @param array          $curlData  Invoice information.
     * @param string         $gatewayId MyFatoorah Gateway ID (default value: '0').
     * @param integer|string $orderId   It used in log file (default value: null).
     * @param string         $sessionId The payment session used in embedded payment.
     *
     * @return array of invoiceURL and invoiceURL
     */
    public function getInvoiceURL($curlData, $gatewayId = 0, $orderId = null, $sessionId = null)
    {

        $this->log('------------------------------------------------------------');

        $curlData['CustomerEmail'] = empty($curlData['CustomerEmail']) ? null : $curlData['CustomerEmail'];

        if (!empty($sessionId)) {
            return $this->embeddedPayment($curlData, $sessionId, $orderId);
        } elseif ($gatewayId == 'myfatoorah' || empty($gatewayId)) {
            return $this->sendPayment($curlData, $orderId);
        } else {
            return $this->excutePayment($curlData, $gatewayId, $orderId);
        }
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * (POST API)
     *
     * @param array          $curlData  Invoice information.
     * @param integer|string $gatewayId MyFatoorah Gateway ID.
     * @param integer|string $orderId   It used in log file (default value: null).
     *
     * @return array
     */
    private function excutePayment($curlData, $gatewayId, $orderId = null)
    {

        $curlData['PaymentMethodId'] = $gatewayId;

        $json = $this->callAPI("$this->apiURL/v2/ExecutePayment", $curlData, $orderId, 'Excute Payment'); //__FUNCTION__

        return ['invoiceURL' => $json->Data->PaymentURL, 'invoiceId' => $json->Data->InvoiceId];
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Create an invoice Link (POST API)
     *
     * @param array          $curlData Invoice information.
     * @param integer|string $orderId  It used in log file (default value: null).
     *
     * @return array
     */
    private function sendPayment($curlData, $orderId = null)
    {

        $curlData['NotificationOption'] = 'Lnk';

        $json = $this->callAPI("$this->apiURL/v2/SendPayment", $curlData, $orderId, 'Send Payment');

        return ['invoiceURL' => $json->Data->InvoiceURL, 'invoiceId' => $json->Data->InvoiceId];
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Create an invoice using Embedded session (POST API)
     *
     * @param array          $curlData  Invoice information.
     * @param integer|string $sessionId Session id used in payment process.
     * @param integer|string $orderId   It used in log file (default value: null).
     *
     * @return array
     */
    private function embeddedPayment($curlData, $sessionId, $orderId = null)
    {

        $curlData['SessionId'] = $sessionId;

        $json = $this->callAPI("$this->apiURL/v2/ExecutePayment", $curlData, $orderId, 'Embedded Payment');
        return ['invoiceURL' => $json->Data->PaymentURL, 'invoiceId' => $json->Data->InvoiceId];
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get session Data (POST API)
     *
     * @param string         $userDefinedField Customer Identifier to dispaly its saved data.
     * @param integer|string $orderId          It used in log file (default value: null).
     *
     * @return object
     */
    public function getEmbeddedSession($userDefinedField = '', $orderId = null)
    {

        $customerIdentifier = ['CustomerIdentifier' => $userDefinedField];

        $json = $this->callAPI("$this->apiURL/v2/InitiateSession", $customerIdentifier, $orderId, 'Initiate Session');
        return $json->Data;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Register Apple Pay Domain (POST API)
     *
     * @param string $url Site URL
     *
     * @return object
     */
    public function registerApplePayDomain($url)
    {

        $domainName = ['DomainName' => parse_url($url, PHP_URL_HOST)];
        return $this->callAPI("$this->apiURL/v2/RegisterApplePayDomain", $domainName, '', 'Register Apple Pay Domain');
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------
}
