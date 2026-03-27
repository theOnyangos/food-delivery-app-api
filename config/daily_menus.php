<?php

return [
    'cache_ttl' => (int) env('DAILY_MENUS_CACHE_TTL', 600),
    'stats_cache_ttl' => (int) env('DAILY_MENUS_STATS_CACHE_TTL', 60),
    'cache_tag' => env('DAILY_MENUS_CACHE_TAG', 'daily_menus'),
    'cache_prefix' => env('DAILY_MENUS_CACHE_PREFIX', 'asl:daily_menus:'),
];
