<?php

namespace MyFatoorah\Library;

use Exception;

/**
 *  TraitDrawInvoice draw the invoice into the checkout page.
 *
 * @author    MyFatoorah <tech@myfatoorah.com>
 * @copyright 2021 MyFatoorah, All rights reserved
 * @license   GNU General Public License v3.0
 */
trait TraitDrawInvoice
{
        //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * List available Payment Methods
     *
     * @param double|integer $invoiceValue
     * @param string         $displayCurrencyIso
     * @param bool           $isAppleRegistered
     *
     * @return array
     */
    public function getPaymentMethodsForDisplay($invoiceValue, $displayCurrencyIso, $isAppleRegistered = false)
    {

        if (!empty(self::$paymentMethods)) {
            return self::$paymentMethods;
        }

        $gateways = $this->getVendorGateways($invoiceValue, $displayCurrencyIso);
        $allRates = $this->getCurrencyRates();

        self::$paymentMethods = ['all' => [], 'cards' => [], 'form' => [], 'ap' => []];

        foreach ($gateways as $g) {
            $g->GatewayData = $this->calcGatewayData($g->TotalAmount, $g->CurrencyIso, $g->PaymentCurrencyIso, $allRates);

            self::$paymentMethods = $this->fillPaymentMethodsArray($g, self::$paymentMethods, $isAppleRegistered);
        }

        //add only one ap gateway
        self::$paymentMethods['ap'] = $this->getOneApplePayGateway(self::$paymentMethods['ap'], $displayCurrencyIso, $allRates);

        return self::$paymentMethods;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------
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
}
