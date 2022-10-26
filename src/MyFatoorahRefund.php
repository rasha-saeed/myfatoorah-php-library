<?php

namespace MyFatoorah\Library;

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
     * Refund a given Payment (POST API)
     *
     * @param integer|string        $paymentId    payment id that will be refunded
     * @param double|integer|string $amount       the refund amount
     * @param string                $currencyCode the refund currency
     * @param string                $reason       reason of the refund
     * @param integer|string        $orderId      used in log file (default value: null)
     *
     * @return object
     */
    public function refund($paymentId, $amount, $currencyCode, $reason, $orderId = null)
    {

        $mfListObj = new MyFatoorahList($this->config);
        $rate      = $mfListObj->getCurrencyRate($currencyCode);

        $url = "$this->apiURL/v2/MakeRefund";

        $postFields = [
            'KeyType'                 => 'PaymentId',
            'Key'                     => $paymentId,
            'RefundChargeOnCustomer'  => false,
            'ServiceChargeOnCustomer' => false,
            'Amount'                  => $amount / $rate,
            'Comment'                 => $reason,
        ];

        $json = $this->callAPI($url, $postFields, $orderId, 'Make Refund');
        return $json->Data;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------
}
