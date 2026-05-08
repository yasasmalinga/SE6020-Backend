<?php

return [
    'region' => env('AWS_COGNITO_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
    'user_pool_id' => env('AWS_COGNITO_USER_POOL_ID', ''),
    'app_client_id' => env('AWS_COGNITO_APP_CLIENT_ID', ''),
];
