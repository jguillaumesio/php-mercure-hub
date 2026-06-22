<?php

declare(strict_types=1);

return [
    'auth_cookie_name' => 'mercureAuthorization',
    'jwt' => [
        'algo' => 'HS256',
        'secret' => 'test-secret-do-not-use-in-prod',
    ],
];
