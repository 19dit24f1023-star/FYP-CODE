<?php

require_once 'toyyibpay_config.php';


/*
|--------------------------------------------------------------------------
| CREATE TOYYIBPAY BILL
|--------------------------------------------------------------------------
*/

function createToyyibPayBill(
    $orderNumber,
    $customerName,
    $customerEmail,
    $customerPhone,
    $amount,
    $paymentMethod
) {

    /*
    |--------------------------------------------------------------------------
    | Validate amount
    |--------------------------------------------------------------------------
    */

    $amount = (float) $amount;

    if ($amount <= 0) {

        return [
            'success' => false,
            'message' => 'Invalid payment amount.'
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Amount in Sen
    |--------------------------------------------------------------------------
    */

    $amountInSen = (int) round($amount * 100);


    /*
    |--------------------------------------------------------------------------
    | Payment Channel
    |--------------------------------------------------------------------------
    |
    | 0 = FPX
    | 1 = Credit Card
    | 2 = FPX + Credit Card
    |
    |--------------------------------------------------------------------------
    */

    $paymentChannel = '0';


    /*
    |--------------------------------------------------------------------------
    | DuitNow QR
    |--------------------------------------------------------------------------
    */

    $enableDuitNowQR = '0';
    $chargeDuitNowQR = '0';


    if ($paymentMethod === 'DuitNow QR') {

        $enableDuitNowQR = '1';
    }


    /*
    |--------------------------------------------------------------------------
    | Clean Customer Information
    |--------------------------------------------------------------------------
    */

    $customerName = trim($customerName);
    $customerEmail = trim($customerEmail);
    $customerPhone = trim($customerPhone);


    /*
    |--------------------------------------------------------------------------
    | Bill Name
    |--------------------------------------------------------------------------
    */

    $billName = 'SA Design ' . $orderNumber;


    /*
    | ToyyibPay character limitation
    */

    $billName = preg_replace(
        '/[^A-Za-z0-9 _]/',
        '',
        $billName
    );


    $billName = substr(
        $billName,
        0,
        30
    );


    /*
    |--------------------------------------------------------------------------
    | Bill Description
    |--------------------------------------------------------------------------
    */

    $billDescription = 'SA Design Order ' . $orderNumber;


    $billDescription = preg_replace(
        '/[^A-Za-z0-9 _]/',
        '',
        $billDescription
    );


    $billDescription = substr(
        $billDescription,
        0,
        100
    );


    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    */

    $returnUrl =
        rtrim(WEBSITE_BASE_URL, '/') .
        '/payment_return.php';


    $callbackUrl =
        rtrim(TOYYIBPAY_PUBLIC_URL, '/') .
        '/payment_callback.php';


    /*
    |--------------------------------------------------------------------------
    | ToyyibPay Parameters
    |--------------------------------------------------------------------------
    */

    $data = [

        /*
        | Account
        */

        'userSecretKey' =>
            TOYYIBPAY_SECRET_KEY,

        'categoryCode' =>
            TOYYIBPAY_CATEGORY_CODE,


        /*
        | Bill
        */

        'billName' =>
            $billName,

        'billDescription' =>
            $billDescription,

        'billPriceSetting' =>
            '1',

        'billPayorInfo' =>
            '1',

        'billAmount' =>
            $amountInSen,


        /*
        | URLs
        */

        'billReturnUrl' =>
            $returnUrl,

        'billCallbackUrl' =>
            $callbackUrl,


        /*
        | Order Reference
        */

        'billExternalReferenceNo' =>
            $orderNumber,


        /*
        | Customer
        */

        'billTo' =>
            $customerName,

        'billEmail' =>
            $customerEmail,

        'billPhone' =>
            $customerPhone,


        /*
        | Split Payment
        */

        'billSplitPayment' =>
            '0',

        'billSplitPaymentArgs' =>
            '',


        /*
        | Payment Channel
        */

        'billPaymentChannel' =>
            $paymentChannel,


        /*
        | Email
        */

        'billContentEmail' =>
            'Thank you for purchasing from SA Design.',


        /*
        | Charges
        */

        'billChargeToCustomer' =>
            '1',


        /*
        | Expiry
        */

        'billExpiryDays' =>
            '1'
    ];


    /*
    |--------------------------------------------------------------------------
    | Enable DuitNow QR
    |--------------------------------------------------------------------------
    */

    if ($paymentMethod === 'DuitNow QR') {

        $data['enableDuitNowQR'] =
            $enableDuitNowQR;

        $data['chargeDuitNowQR'] =
            $chargeDuitNowQR;
    }


    /*
    |--------------------------------------------------------------------------
    | CURL
    |--------------------------------------------------------------------------
    */

    $curl = curl_init();


    curl_setopt_array($curl, [

        CURLOPT_POST =>
            true,

        CURLOPT_URL =>
            rtrim(TOYYIBPAY_API_URL, '/') .
            '/createBill',

        CURLOPT_RETURNTRANSFER =>
            true,

        CURLOPT_POSTFIELDS =>
            $data,

        CURLOPT_TIMEOUT =>
            30,

        CURLOPT_CONNECTTIMEOUT =>
            15,

        CURLOPT_SSL_VERIFYPEER =>
            true,

        CURLOPT_SSL_VERIFYHOST =>
            2,

        CURLOPT_FOLLOWLOCATION =>
            true,

        CURLOPT_HTTPHEADER => [
            'Accept: application/json'
        ]
    ]);


    /*
    |--------------------------------------------------------------------------
    | Execute
    |--------------------------------------------------------------------------
    */

    $result = curl_exec($curl);


    /*
    |--------------------------------------------------------------------------
    | CURL Information
    |--------------------------------------------------------------------------
    */

    $curlError =
        curl_error($curl);

    $httpCode =
        curl_getinfo(
            $curl,
            CURLINFO_HTTP_CODE
        );


    curl_close($curl);


    /*
    |--------------------------------------------------------------------------
    | CURL ERROR
    |--------------------------------------------------------------------------
    */

    if ($result === false || $curlError) {

        return [

            'success' =>
                false,

            'message' =>
                'ToyyibPay connection failed: ' .
                $curlError,

            'http_code' =>
                $httpCode
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Decode Response
    |--------------------------------------------------------------------------
    */

    $response =
        json_decode(
            $result,
            true
        );


    /*
    |--------------------------------------------------------------------------
    | Debug / API ERROR
    |--------------------------------------------------------------------------
    */

    if (
        !is_array($response) ||
        empty($response[0]['BillCode'])
    ) {

        /*
         * Preserve ToyyibPay's response in the server log for diagnosis,
         * while only showing a safe, useful message to the customer.
         */
        error_log(
            'ToyyibPay createBill failed. HTTP ' .
            $httpCode .
            '. Response: ' .
            substr($result, 0, 1000)
        );

        $apiMessage = '';
        if (is_array($response)) {
            $errorData = $response[0] ?? $response;
            if (is_array($errorData)) {
                foreach (['message', 'Message', 'msg', 'status', 'Status', 'error', 'Error'] as $key) {
                    if (!empty($errorData[$key]) && is_scalar($errorData[$key])) {
                        $apiMessage = trim((string) $errorData[$key]);
                        break;
                    }
                }
            }
        }

        /* Some ToyyibPay errors are returned as plain text, for example
           [CATEGORY-NOT-MATCH], instead of JSON. */
        if ($apiMessage === '' && preg_match('/^\s*\[([^\]]+)\]\s*$/', $result, $matches)) {
            $apiMessage = $matches[1];
        }

        return [

            'success' =>
                false,

            'message' =>
                $apiMessage !== ''
                    ? 'ToyyibPay: ' . $apiMessage
                    : 'ToyyibPay could not create the payment bill. Please check the payment account configuration.',

            'http_code' =>
                $httpCode,

            'response' =>
                $result
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | BILL CREATED
    |--------------------------------------------------------------------------
    */

    $billCode =
        $response[0]['BillCode'];


    $paymentUrl =
        rtrim(
            TOYYIBPAY_PAYMENT_URL,
            '/'
        ) .
        '/' .
        $billCode;


    return [

        'success' =>
            true,

        'billcode' =>
            $billCode,

        'payment_url' =>
            $paymentUrl,

        'http_code' =>
            $httpCode
    ];
}
