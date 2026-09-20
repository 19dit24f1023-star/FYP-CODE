<?php

/*
|--------------------------------------------------------------------------
| TOYYIBPAY CONFIGURATION
|--------------------------------------------------------------------------
| false = LIVE
| true  = SANDBOX
|--------------------------------------------------------------------------
*/

define('TOYYIBPAY_SANDBOX', false);


/*
|--------------------------------------------------------------------------
| TOYYIBPAY API
|--------------------------------------------------------------------------
*/

if (TOYYIBPAY_SANDBOX) {

    define(
        'TOYYIBPAY_API_URL',
        'https://dev.toyyibpay.com/index.php/api/'
    );

    define(
        'TOYYIBPAY_PAYMENT_URL',
        'https://dev.toyyibpay.com/'
    );

} else {

    define(
        'TOYYIBPAY_API_URL',
        'https://toyyibpay.com/index.php/api/'
    );

    define(
        'TOYYIBPAY_PAYMENT_URL',
        'https://toyyibpay.com/'
    );
}


/*
|--------------------------------------------------------------------------
| TOYYIBPAY ACCOUNT
|--------------------------------------------------------------------------
| IMPORTANT:
| Generate/regenerate your LIVE Secret Key if the old one was exposed.
|--------------------------------------------------------------------------
*/

define(
    'TOYYIBPAY_SECRET_KEY',
    'cvwc7r07-etx0-on1d-8thg-14m568amulsf'
);


define(
    'TOYYIBPAY_CATEGORY_CODE',
    '02libf66'
);


/*
|--------------------------------------------------------------------------
| WEBSITE URL
|--------------------------------------------------------------------------
| Local website:
| http://localhost/fyp_code
|
| This is used for billReturnUrl.
|--------------------------------------------------------------------------
*/

define(
    'WEBSITE_BASE_URL',
    'https://architectural-panel-aud-tears.trycloudflare.com/fyp_code'
);


/*
|--------------------------------------------------------------------------
| CLOUDFLARE PUBLIC URL
|--------------------------------------------------------------------------
| Example:
|
| https://payment.yourdomain.com
|
| DO NOT put /payment_callback.php here.
| The create bill file will add it automatically.
|--------------------------------------------------------------------------
*/

define(
    'TOYYIBPAY_PUBLIC_URL',
    'https://architectural-panel-aud-tears.trycloudflare.com/fyp_code'
);
