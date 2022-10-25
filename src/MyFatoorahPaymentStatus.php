<?php

namespace MyFatoorah\Library;

use Exception;

/**
 *  MyFatoorahPaymentStatus handles the payment status of MyFatoorah API endpoints
 *
 * @author    MyFatoorah <tech@myfatoorah.com>
 * @copyright 2021 MyFatoorah, All rights reserved
 * @license   GNU General Public License v3.0
 */
class MyFatoorahPaymentStatus extends MyFatoorahPayment {
    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get the Payment Transaction Status (POST API)
     *
     * @param string         $keyId
     * @param string         $KeyType
     * @param integer|string $orderId (default value: null)
     * @param string         $price
     * @param string         $currncy
     *
     * @return object
     *
     * @throws Exception
     */
    public function getPaymentStatus($keyId, $KeyType, $orderId = null, $price = null, $currncy = null) {

        //payment inquiry
        $curlData = ['Key' => $keyId, 'KeyType' => $KeyType];
        $json     = $this->callAPI("$this->apiURL/v2/GetPaymentStatus", $curlData, $orderId, 'Get Payment Status');

        $msgLog = 'Order #' . $json->Data->CustomerReference . ' ----- Get Payment Status';

        //check for the order information
        if (!self::checkOrderInformation($json->Data, $orderId, $price, $currncy)) {
            $err = 'Trying to call data of another order';
            $this->log("$msgLog - Exception is $err");
            throw new Exception($err);
        }

        //check invoice status (Paid and Not Paid Cases)
        if ($json->Data->InvoiceStatus == 'Paid' || $json->Data->InvoiceStatus == 'DuplicatePayment') {
            $json->Data = self::getSuccessData($json);
            $this->log("$msgLog - Status is Paid");
        } elseif ($json->Data->InvoiceStatus != 'Paid') {
            $json->Data = self::getErrorData($json, $keyId, $KeyType);
            $this->log("$msgLog - Status is " . $json->Data->InvoiceStatus . '. Error is ' . $json->Data->InvoiceError);
        }

        return $json->Data;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     *
     * @param object $data
     * @param string $orderId
     * @param string $price
     * @param string $currncy
     *
     * @return boolean
     */
    private static function checkOrderInformation($data, $orderId = null, $price = null, $currncy = null) {

        //check for the order ID
        if ($orderId && $orderId != $data->CustomerReference) {
            return false;
        }

        //check for the order price and currency
        list($valStr, $mfCurrncy) = explode(' ', $data->InvoiceDisplayValue);
        $mfPrice = floatval(preg_replace('/[^\d.]/', '', $valStr));

        if ($price && $price != $mfPrice) {
            return false;
        }
        
        return !($currncy && $currncy != $mfCurrncy);
        
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     *
     * @param object $json
     *
     * @return object
     */
    private static function getSuccessData($json) {

        foreach ($json->Data->InvoiceTransactions as $transaction) {
            if ($transaction->TransactionStatus == 'Succss') {
                $json->Data->InvoiceStatus = 'Paid';
                $json->Data->InvoiceError  = '';

                $json->Data->focusTransaction = $transaction;
                return $json->Data;
            }
        }
        return $json->Data;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     *
     * @param object $json
     * @param string $keyId
     * @param string $KeyType
     *
     * @return object
     */
    private static function getErrorData($json, $keyId, $KeyType) {

        //------------------
        //case 1: payment is Failed
        $focusTransaction = self::{"getLastTransactionOf$KeyType"}($json, $keyId);
        if ($focusTransaction && $focusTransaction->TransactionStatus == 'Failed') {
            $json->Data->InvoiceStatus = 'Failed';
            $json->Data->InvoiceError  = $focusTransaction->Error . '.';

            $json->Data->focusTransaction = $focusTransaction;

            return $json->Data;
        }

        //------------------
        //case 2: payment is Expired
        //all myfatoorah gateway is set to Asia/Kuwait
        $ExpiryDateTime = $json->Data->ExpiryDate . ' ' . $json->Data->ExpiryTime;
        $ExpiryDate     = new \DateTime($ExpiryDateTime, new \DateTimeZone('Asia/Kuwait'));
        $currentDate    = new \DateTime('now', new \DateTimeZone('Asia/Kuwait'));

        if ($ExpiryDate < $currentDate) {
            $json->Data->InvoiceStatus = 'Expired';
            $json->Data->InvoiceError  = 'Invoice is expired since ' . $json->Data->ExpiryDate . '.';

            return $json->Data;
        }

        //------------------
        //case 3: payment is Pending
        //payment is pending .. user has not paid yet and the invoice is not expired
        $json->Data->InvoiceStatus = 'Pending';
        $json->Data->InvoiceError  = 'Pending Payment.';

        return $json->Data;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     *
     * @param object         $json
     * @param integer|string $keyId
     *
     * @return object
     */
    private static function getLastTransactionOfPaymentId($json, $keyId) {

        foreach ($json->Data->InvoiceTransactions as $transaction) {
            if ($transaction->PaymentId == $keyId && $transaction->Error) {
                return $transaction;
            }
        }
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     *
     * @param object $json
     *
     * @return object
     */
    private static function getLastTransactionOfInvoiceId($json) {

        usort($json->Data->InvoiceTransactions, function ($a, $b) {
            return strtotime($a->TransactionDate) - strtotime($b->TransactionDate);
        });

        return end($json->Data->InvoiceTransactions);
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------
}
