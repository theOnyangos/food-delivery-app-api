<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key
    |--------------------------------------------------------------------------
    | Set in .env as OPENAI_API_KEY
    */
    'api_key' => env('OPENAI_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Default OpenAI Model
    |--------------------------------------------------------------------------
    */
    'default_model' => env('OPENAI_MODEL', 'gpt-3.5-turbo'),

    /*
    |--------------------------------------------------------------------------
    | Max Tokens / Temperature
    |--------------------------------------------------------------------------
    */
    'max_tokens' => (int) env('AI_MAX_TOKENS', 1000),
    'temperature' => (float) env('AI_TEMPERATURE', 0.7),

    /*
    |--------------------------------------------------------------------------
    | Context & Rate Limiting
    |--------------------------------------------------------------------------
    */
    'max_history_messages' => (int) env('AI_MAX_HISTORY_MESSAGES', 10),
    'rate_limit_per_user' => (int) env('AI_RATE_LIMIT_PER_USER', 30),
    'rate_limit_per_ip' => (int) env('AI_RATE_LIMIT_PER_IP', 60),

    /*
    |--------------------------------------------------------------------------
    | Daily Limits (0 = unlimited)
    |--------------------------------------------------------------------------
    | Partners: use daily_limit_partner when set; otherwise daily_limit_customer.
    | Staff (Super Admin, Admin): daily_limit_admin.
    */
    'daily_limit_customer' => (int) env('AI_DAILY_LIMIT_CUSTOMER', 5),
    'daily_limit_admin' => (int) env('AI_DAILY_LIMIT_ADMIN', 0),
    'daily_limit_partner' => (int) env('AI_DAILY_LIMIT_PARTNER', 0),

    /*
    |--------------------------------------------------------------------------
    | Optional OpenAI Assistant ID (Assistants API)
    |--------------------------------------------------------------------------
    */
    'assistant_id' => env('OPENAI_ASSISTANT_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Enable / Disable AI Agent
    |--------------------------------------------------------------------------
    */
    'enabled' => filter_var(env('AI_AGENT_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | System Prompts by User Type
    |--------------------------------------------------------------------------
    | vendor = Partner. admin = Super Admin, Admin. customer = assistant-style KB chat.
    */
    'system_prompts' => [
        'vendor' => 'You are a helpful assistant for Amazing Souls (ASL), a meal and community platform. ONLY answer questions related to ASL: partner meals, uploads, recipes, delivery, account, and platform navigation. If asked about unrelated topics, politely redirect to ASL or suggest contacting support. Be friendly, professional, and concise.',
        'admin' => 'You are an administrative assistant for Amazing Souls (ASL). Help staff with content, users, meals, delivery zones, notifications, and platform operations. Focus on ASL administration. If asked about unrelated topics, redirect to relevant platform features or support.',
        'customer' => 'You are a helpful assistant for Amazing Souls (ASL). Answer questions about the application, meals, delivery, and how to get started. Be friendly, professional, and concise. If you don\'t have enough information, suggest contacting support.',
    ],

];
