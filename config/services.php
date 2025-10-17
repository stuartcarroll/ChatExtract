<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Azure OpenAI Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Azure OpenAI service used for story detection.
    | Set enabled to true and provide your Azure OpenAI credentials.
    |
    */

    'azure_openai' => [
        'enabled' => env('AZURE_OPENAI_ENABLED', false),
        'endpoint' => env('AZURE_OPENAI_ENDPOINT'),
        'api_key' => env('AZURE_OPENAI_API_KEY'),
        'deployment_name' => env('AZURE_OPENAI_DEPLOYMENT_NAME', 'gpt-4'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Claude API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Anthropic's Claude API used for story detection.
    | Set enabled to true and provide your Claude API key.
    |
    */

    'claude' => [
        'enabled' => env('CLAUDE_ENABLED', false),
        'api_key' => env('CLAUDE_API_KEY'),
        'model' => env('CLAUDE_MODEL', 'claude-3-haiku-20240307'),
    ],

];
