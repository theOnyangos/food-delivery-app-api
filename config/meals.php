<?php

return [
    'cache_ttl' => env('MEALS_CACHE_TTL', 600),
    'cache_tag' => env('MEALS_CACHE_TAG', 'meals'),
    'cache_prefix' => env('MEALS_CACHE_PREFIX', 'asl:meals:'),
];
