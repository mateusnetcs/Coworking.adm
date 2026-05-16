<?php

return [

    'admin_email' => env('ADMIN_EMAIL'),

    'frontend_url' => rtrim(env('FRONTEND_URL', env('APP_URL', 'http://localhost')), '/'),

];
