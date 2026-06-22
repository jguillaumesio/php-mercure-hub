<?php
// PHPUnit bootstrap: provide a default MERCURE_CONFIG_PATH if not already set,
// so class instantiation in unit tests doesn't fail on config loading.

if (getenv('MERCURE_CONFIG_PATH') === false || getenv('MERCURE_CONFIG_PATH') === '') {
    $tmpConfig = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-mercure-hub-test-config.php';
    if (!file_exists($tmpConfig)) {
        $contents = '<?php return ' . var_export([
            'auth_cookie_name' => 'mercureAuthorization',
            'jwt' => ['algo' => 'HS256', 'secret' => 'secret'],
        ], true) . ';';
        file_put_contents($tmpConfig, $contents);
    }
    putenv('MERCURE_CONFIG_PATH=' . $tmpConfig);
}
