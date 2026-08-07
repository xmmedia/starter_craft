<?php

use craft\helpers\App;

return [
    // the contact form names: used for the hidden formName field in the form
    // templates & the notification email
    // anything else submitted is treated as an unknown form
    'formNames' => [
        'contact' => 'Contact',
    ],
    'gaTrackingId' => App::env('GA_TRACKING_ID'),
];
