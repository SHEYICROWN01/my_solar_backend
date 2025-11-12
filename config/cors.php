<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Local development
        'http://localhost:3000',
        'http://localhost:8080',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:8080',
        'http://localhost:3001',
        'http://localhost:5173', // Vite default
        'http://localhost:5174', // Vite alternative
        
        // Production domains
        'https://ggtl.com',
        'https://www.ggtl.com',
        'https://api.ggtl.com',
        'https://shop.gifamz.com', // Frontend production domain
        
        // Vercel deployment domains (add your actual Vercel URL)
        'https://your-frontend-app.vercel.app',
    ],

    'allowed_origins_patterns' => [
        // Allow all Vercel preview deployments
        '/^https:\/\/.*\.vercel\.app$/'
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
