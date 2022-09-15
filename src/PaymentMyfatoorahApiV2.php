<?php

namespace MyFatoorah\Library;

use MyFatoorah\Library\MyfatoorahApiV2;
use Exception;

/**
 *  PaymentMyfatoorahApiV2 handle the payment process of MyFatoorah API endpoints
 *
 * @author    MyFatoorah <tech@myfatoorah.com>
 * @copyright 2021 MyFatoorah, All rights reserved
 * @license   GNU General Public License v3.0
 */
class PaymentMyfatoorahApiV2 extends MyfatoorahApiV2
{



    /**
     *
     * @var string
     */
    public static $pmCachedFile = __DIR__ . '/mf-methods.json';



    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * List available Payment Gateways. (POST API)
     *
     * @param double|integer $invoiceValue
     * @param string         $displayCurrencyIso
     * @param boolean        $isCached
     *
     * @return array
     */
    public function getVendorGateways($invoiceValue = 0, $displayCurrencyIso = '', $isCached = false)
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


}
