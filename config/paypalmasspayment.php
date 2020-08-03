<?php

return [


    /*
     * This is Authentication type, You can set it to 'api_certificate' or 'api_signature'
     */
    'authentication'    => 'api_signature',


    /*
     * You can set it to 'sandbox' or 'live'
     */
    'environment'       => 'sandbox',


    /*
     * You can set it to 'nvp' or 'soap'
     */
    'operation_type'    => 'nvp',


    /*
     * You can set it to any valid version
     */
    'api_vesion'        => '51.0',


    /*
     * You can set it to 'email' or 'phone' or 'id'
     */
    'receiver_type'     => 'email',


    /*
     * You can set currency here
     */
    'currency'          => 'USD',
    /*
     * or other currency ('USD', 'BRL', 'GBP', 'EUR', 'JPY', 'CAD', 'AUD')
     * https://developer.paypal.com/docs/classic/api/currency_codes/
     */


    /*
     * These are sandbox credentials
     * You can set API Username and API Password here
     * If you set authentication as 'api_signature' then you must enter 'api_signature' here
     */
    'sandbox' => [

		        'api_username'    => 'rsacc.seller-facilitator_api1.yahoo.com',

		        'api_password'    => '7FRCTXSKEL9RMPAA',

                /*
                * If you set authentication as 'api_certificate' then you must enter 'api_certificate' here
                * If it is 'api_certificate' you must give proper path to cert_key_pem.txt file
                */
		        'api_certificate' => '',

                /*
                 * If you set authentication as 'api_signature' then you must enter 'api_signature' here
                 */
		        'api_signature'   => 'ASW3i3C1uypgc-fHKJdG-oM9NfSEACkYpGJ-ItF4RYYarHlSSAL5Ex7q',
	   ],

    /*
     * These are live credentials
     * You can set API Username and API Password here

     * If you set authentication as 'api_certificate' then you must enter 'api_certificate' here
     * If it is 'api_certificate' you must give proper path to cert_key_pem.txt file

     * If you set authentication as 'api_signature' then you must enter 'api_signature' here
     */
    'live' => [

		       'api_username'    => 'chitchatworldwide_api1.gmail.com',

		       'api_password'    => '8UWBF7UUCHR2ZLUM',

               /*
                * If you set authentication as 'api_certificate' then you must enter 'api_certificate' here
                * If it is 'api_certificate' you must give proper path to cert_key_pem.txt file
                */
		       'api_certificate' => '',

               /*
                * If you set authentication as 'api_signature' then you must enter 'api_signature' here
                */
		       'api_signature'   => 'AFcWxV21C7fd0v3bYYYRCpSSRl31AXbru.URwoLS9InWUMAcZUbTe1VZ',
		],


    ];
