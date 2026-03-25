<?php

return [
    'settings_key' => 'chat_settings',

    'defaults' => [
        'working_hours_enabled' => false,
        'start_time' => '09:00',
        'end_time' => '17:00',
        'timezone' => 'Africa/Nairobi',
        'out_of_hours_message' => 'Support is currently offline. We will respond during working hours.',
        'allow_messages_outside_hours' => true,
    ],

    'messages_per_page' => 20,
    'conversations_per_page' => 15,
];
