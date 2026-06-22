<?php
// PHPUnit bootstrap: provide a default MERCURE_CONFIG_PATH if not already set,
// so class instantiation in unit tests doesn't fail on config loading.

if (getenv('MERCURE_CONFIG_PATH') === false || getenv('MERCURE_CONFIG_PATH') === '') {
    $tmpDir     = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-mercure-hub-test';
    $keyFile    = $tmpDir . DIRECTORY_SEPARATOR . 'hmac.key';
    $tmpConfig  = $tmpDir . DIRECTORY_SEPARATOR . 'config.php';
    if (!is_dir($tmpDir)) {
        mkdir($tmpDir, 0700, true);
    }
    if (!file_exists($keyFile)) {
        file_put_contents($keyFile, 'test-secret-' . bin2hex(random_bytes(16)));
    }
    if (!file_exists($tmpConfig)) {
        $contents = '<?php return ' . var_export([
            'auth_cookie_name' => 'mercureAuthorization',
            'jwt' => [
                // Paths in config are relative to getcwd() per JWTManager::checkJWTConfigValidity(),
                // so make this absolute-to-cwd via the leading slash... but file_exists()
                // checks getcwd() . secret. We set MERCURE_CONFIG-JWT-SECRET-DIR to bypass that.
                'algo'   => 'HS256',
                'secret' => $keyFile,
            ],
        ], true) . ';';
        file_put_contents($tmpConfig, $contents);
    }
    putenv('MERCURE_CONFIG_PATH=' . $tmpConfig);
}
