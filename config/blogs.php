<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public blog listing/detail cache TTL (seconds)
    |--------------------------------------------------------------------------
    */
    'cache_ttl' => (int) env('BLOGS_CACHE_TTL', 300),

    /*
    |--------------------------------------------------------------------------
    | Redis tagged-cache tag for all public blog reads (lists, recent, categories, show)
    |--------------------------------------------------------------------------
    */
    'public_tag' => env('BLOGS_CACHE_TAG', 'blogs_public'),

];
