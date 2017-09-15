<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Announce interval
    |--------------------------------------------------------------------------
    |
    | The interval (in minutes) between two regular announce requests.
    |
    */

    'announce_interval' => env('ANNOUNCE_INTERVAL', 45),

    /*
    |--------------------------------------------------------------------------
    | Minimum announce interval
    |--------------------------------------------------------------------------
    |
    | The minimum interval (in minutes) between two announce requests.
    |
    */

    'min_announce_interval' => env('MIN_ANNOUNCE_INTERVAL', 10),

];
