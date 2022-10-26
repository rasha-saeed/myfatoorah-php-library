<?php

namespace MyFatoorah\Library\API\Payment;

use MyFatoorah\Library\API\MyFatoorahList;
/**
 *  MyFatoorahPaymentForm handles the form process of MyFatoorah API endpoints
 *
 * @author    MyFatoorah <tech@myfatoorah.com>
 * @copyright 2021 MyFatoorah, All rights reserved
 * @license   GNU General Public License v3.0
 */
class MyFatoorahPaymentEmbedded extends MyFatoorahPayment
{

    /**
     * The paymentMethods array is used to display the payment in the checkout page.
     *
     * @var array
     */
    protected static $paymentMethods;

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * List available Payment Methods
     *
     * @param double|integer $invoiceValue       Total invoice amount.
     * @param string         $displayCurrencyIso Total invoice currency.
     * @param bool           $isAppleRegistered  Is site domain is registered with applePay and MyFatoorah or not.
     *
     * @return array
     */
    public function getPaymentMethodsForDisplay($invoiceValue, $displayCurrencyIso, $isAppleRegistered)
    {

        if (!empty(self::$paymentMethods)) {
            return self::$paymentMethods;
        }

        $gateways = $this->initiatePayment($invoiceValue, $displayCurrencyIso);

        $mfListObj = new MyFatoorahList($this->config);
        $allRates  = $mfListObj->getCurrencyRates();

        self::$paymentMethods = ['all' => [], 'cards' => [], 'form' => [], 'ap' => []];

        foreach ($gateways as $gateway) {
            $gateway->GatewayData = $this->calcGatewayData($gateway->TotalAmount, $gateway->CurrencyIso, $gateway->PaymentCurrencyIso, $allRates);

            self::$paymentMethods = $this->addGatewayToPaymentMethodsArray($gateway, self::$paymentMethods, $isAppleRegistered);
        }
        if ($isAppleRegistered) {
            //add only one ap gateway
            self::$paymentMethods['ap'] = $this->getOneApplePayGateway(self::$paymentMethods['ap'], $displayCurrencyIso, $allRates);
        }
        return self::$paymentMethods;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Calculate the amount value that will be paid in each gateway
     *
     * @param double|integer $totalAmount        The total amount of the invoice.
     * @param string         $currency           The currency of the invoice total amount.
     * @param string         $paymentCurrencyIso The currency of the gateway.
     * @param array          $allRates           The MyFatoorah currency rate array of all gateways.
     *
     * @return array
     */
    protected function calcGatewayData($totalAmount, $currency, $paymentCurrencyIso, $allRates)
    {

        foreach ($allRates as $data) {
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
     * Returns One Apple pay array in case multiple are enabled in the account
     *
     * @param array  $apGateways      The all available AP gateways
     * @param string $displayCurrency The currency of the invoice total amount.
     * @param array  $allRates        The MyFatoorah currency rate array of all gateways.
     *
     * @return array
     */
    protected function getOneApplePayGateway($apGateways, $displayCurrency, $allRates)
    {

        $displayCurrencyIndex = array_search($displayCurrency, array_column($apGateways, 'PaymentCurrencyIso'));
        if ($displayCurrencyIndex) {
            return $apGateways[$displayCurrencyIndex];
        }

        //get defult mf account currency
        $defCurKey       = array_search('1', array_column($allRates, 'Value'));
        $defaultCurrency = $allRates[$defCurKey]->Text;

        $defaultCurrencyIndex = array_search($defaultCurrency, array_column($apGateways, 'PaymentCurrencyIso'));
        if ($defaultCurrencyIndex) {
            return $apGateways[$defaultCurrencyIndex];
        }

        if (isset($apGateways[0])) {
            return $apGateways[0];
        }

        return [];
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------
}
