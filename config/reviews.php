<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Meal listing reviews cache TTL (seconds) for public/partner list
    |--------------------------------------------------------------------------
    */
    'cache_ttl' => (int) env('REVIEWS_CACHE_TTL', 300),

    /*
    |--------------------------------------------------------------------------
    | Cache tag prefix for review listing data (Redis tagged cache)
    |--------------------------------------------------------------------------
    */
    'cache_tag_prefix' => env('REVIEWS_CACHE_TAG_PREFIX', 'review_listing'),

    /*
    |--------------------------------------------------------------------------
    | Tag for aggregated "all meal reviews" feed cache
    |--------------------------------------------------------------------------
    */
    'all_meal_reviews_tag' => 'meal_reviews_all',

];
