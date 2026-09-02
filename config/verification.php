<?php

return [

    'otp' => [

        'length' => 6,

        'expires_in' => 2,

        'max_sends' => 3,

        'send_window' => 10,

        'fingerprint_max_sends' => 6,
        'fingerprint_send_window' => 10,

        'ip_max_sends' => 30,
        'ip_send_window' => 10,

    ],

];
