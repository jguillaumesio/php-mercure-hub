<?php
// PHPUnit bootstrap: provide a default MERCURE_CONFIG_PATH if not already set,
// so class instantiation in unit tests doesn't fail on config loading.

if (getenv('MERCURE_CONFIG_PATH') === false || getenv('MERCURE_CONFIG_PATH') === '') {
    $tmpDir    = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-mercure-hub-test';
    $tmpConfig = $tmpDir . DIRECTORY_SEPARATOR . 'config.php';
    if (!is_dir($tmpDir)) {
        mkdir($tmpDir, 0700, true);
    }
    if (!file_exists($tmpConfig)) {
        $contents = '<?php return ' . var_export([
            'auth_cookie_name' => 'mercureAuthorization',
            'jwt' => [
                'algo'   => 'HS256',
                'secret' => 'test-secret-' . bin2hex(random_bytes(16)),
            ],
        ], true) . ';';
        file_put_contents($tmpConfig, $contents);
    }
    putenv('MERCURE_CONFIG_PATH=' . $tmpConfig);
}
