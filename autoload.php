<?php

/**
 * This file is responsible for updating the MyFatoorah Library file everyday
 *
 * MyFatoorah offers a seamless business experience by offering a technology put together by our tech team. This enables smooth business operations involving sales activity, product invoicing, shipping, and payment processing. MyFatoorah invoicing and payment gateway solution trigger your business to greater success at all levels in the new age world of commerce. Leverage your sales and payments at all e-commerce platforms (ERPs, CRMs, CMSs) with transparent and slick applications that are well-integrated into social media and telecom services. For every closing sale click, you make a business function gets done for you, along with generating factual reports and statistics to fine-tune your business plan with no-barrier low-cost.
 * Our technology experts have designed the best GCC E-commerce solutions for the native financial instruments (Debit Cards, Credit Cards, etc.) supporting online sales and payments, for events, shopping, mall, and associated services.
 *
 * Created by MyFatoorah http://www.myfatoorah.com/
 * Developed By tech@myfatoorah.com
 * Date: 17/01/2022
 * Time: 12:00
 *
 * API Documentation on https://myfatoorah.readme.io/docs
 * Library Documentation and Download link on https://myfatoorah.readme.io/docs/php-library
 *
 * @author MyFatoorah <tech@myfatoorah.com>
 * @copyright 2021 MyFatoorah, All rights reserved
 * @license GNU General Public License v3.0
 */
$mfLibFolder = __DIR__ . '/src/';
$mfLibFile   = $mfLibFolder . '/MyFatoorah.php';

if (!file_exists($mfLibFile) || (time() - filemtime($mfLibFile) > 86400)) {

    $mfCurl = curl_init('https://portal.myfatoorah.com/Files/API/php/library/2.1/MyFatoorahLibrary.txt');
    curl_setopt_array($mfCurl, [CURLOPT_RETURNTRANSFER => true]);

    $mfResponse = curl_exec($mfCurl);
    $mfHttpCode = curl_getinfo($mfCurl, CURLINFO_HTTP_CODE);

    curl_close($mfCurl);

    if ($mfHttpCode == 200 && is_string($mfResponse)) {

        $mfNamespace           = '<?php namespace MyFatoorah\Library; ';
        $mfApiNamespace        = '<?php namespace MyFatoorah\Library\API; ';
        $mfApiPaymentNamespace = '<?php namespace MyFatoorah\Library\API\Payment; ';

        $useMfClass     = 'use MyFatoorah\Library\MyFatoorah; ';
        $useMfListClass = 'use MyFatoorah\Library\API\MyFatoorahList; ';
        $useExClass     = 'use Exception; ';

        $mfClass = 'class ';

        $mfSplitFile = explode('class', $mfResponse);

        file_put_contents($mfLibFolder . 'MyFatoorah.php', $mfNamespace . $useExClass . $mfClass . $mfSplitFile[1]);
        file_put_contents($mfLibFolder . 'MyFatoorahHelper.php', $mfNamespace . $useExClass . $mfClass . $mfSplitFile[2]);

        $mfLibFolder .= 'API/';
        file_put_contents($mfLibFolder . 'MyFatoorahList.php', $mfApiNamespace . $useMfClass . $useExClass . $mfClass . $mfSplitFile[3]);
        file_put_contents($mfLibFolder . 'MyFatoorahRefund.php', $mfApiNamespace . $useMfClass . $mfClass . $mfSplitFile[4]);
        file_put_contents($mfLibFolder . 'MyFatoorahShipping.php', $mfApiNamespace . $useMfClass . $mfClass . $mfSplitFile[5]);

        $mfLibFolder .= 'Payment/';
        file_put_contents($mfLibFolder . 'MyFatoorahPayment.php', $mfApiPaymentNamespace . $useMfClass . $useExClass . $mfClass . $mfSplitFile[6]);
        file_put_contents($mfLibFolder . 'MyFatoorahPaymentEmbedded.php', $mfApiPaymentNamespace . $useMfListClass . $mfClass . $mfSplitFile[7]);
        file_put_contents($mfLibFolder . 'MyFatoorahPaymentStatus.php', $mfApiPaymentNamespace . $useExClass . $mfClass . $mfSplitFile[8]);
    } elseif ($mfHttpCode == 403) {
        touch($mfLibFile);
    }
}
