<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Edura System Endpoint
    |--------------------------------------------------------------------------
    |
    | This value represents the base URL of the Edura system that the current
    | school-app instance should talk to when proxying requests (grading
    | policies, score submissions, etc). It is typically stored in .env as
    | EDURA_ENDPOINT.
    |
    */
    'endpoint' => env('EDURA_ENDPOINT'),

    /*
    |--------------------------------------------------------------------------
    | Edura System API Token
    |--------------------------------------------------------------------------
    |
    | This token authenticates the school-app when it communicates back to the
    | Edura system. Store it securely in .env as EDURA_API_TOKEN.
    |
    */
    'api_token' => env('EDURA_API_TOKEN'),
];

