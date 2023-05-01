<?php

namespace MyFatoorah\Library\API;

use MyFatoorah\Library\MyFatoorah;

/**
 *  MyFatoorahRefund handles the refund process of MyFatoorah API endpoints
 *
 * @author    MyFatoorah <tech@myfatoorah.com>
 * @copyright 2021 MyFatoorah, All rights reserved
 * @license   GNU General Public License v3.0
 */
class MyFatoorahRefund extends MyFatoorah
{
    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * (deprecated function) use makeRefund instead
     * Refund a given PaymentId or InvoiceId
     * 
     * @param integer|string        $keyId        payment id that will be refunded
     * @param double|integer|string $amount       the refund amount
     * @param string                $currencyCode the amount currency
     * @param string                $comment      reason of the refund
     * @param integer|string        $orderId      used in log file (default value: null)
     * @param type                  $keyType      supported keys are (InvoiceId, PaymentId)
     * 
     * @return object
     */
    public function refund($keyId, $amount, $currencyCode = null, $comment = null, $orderId = null, $keyType = 'PaymentId')
    {
        $postFields = [
            'Key'                     => $keyId,
            'KeyType'                 => $keyType,
            'RefundChargeOnCustomer'  => false,
            'ServiceChargeOnCustomer' => false,
            'Amount'                  => $amount,
            'CurrencyIso'             => $currencyCode,
            'Comment'                 => $comment,
        ];

        return $this->makeRefund($postFields, $orderId);
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Call makeRefund API (POST API)
     * 
     * @param object            $curlData   Refund information
     * @param integer|string    $orderId    Used in log file (default value: null)
     * 
     * @return object
     */
    public function makeRefund($curlData, $orderId = null)
    {
        $url  = "$this->apiURL/v2/MakeRefund";
        $json = $this->callAPI($url, $curlData, $orderId, 'Make Refund');
        return $json->Data;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------
}
