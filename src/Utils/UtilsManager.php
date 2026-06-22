<?php

namespace Jguillaumesio\PhpMercureHub\Utils;

use Jguillaumesio\PhpMercureHub\Config;

/**
 * Static proxy that forwards all calls to the configured Utils implementation.
 *
 * The concrete class is chosen at first call via Config['utils'] (must implement
 * UtilsInterface). Falls back to Utils.
 *
 * @method static void   setHeader(string $key, string $value, bool $replace = true)
 * @method static void   setHeaders(array $headers, bool $replace)
 * @method static array  getHeaders()
 * @method static array  getQueryParams()
 * @method static array  getCookies()
 * @method static array  getRequestBody()
 * @method static array  getAvailableResponseTypes()
 * @method static string generateResponse(\Jguillaumesio\PhpMercureHub\Models\Topic $topic, array $request)
 */
class UtilsManager
{
    private static $class;

    private static function getInstance()
    {
        if (self::$class === null) {
            $config = Config::getConfig();
            self::$class = (isset($config['utils']) && \is_string($config['utils']) && \class_exists($config['utils'])) ? $config['utils'] : Utils::class;
        }
        return self::$class;
    }

    public static function __callStatic($method, $arguments)
    {
        return self::getInstance()::{$method}(...$arguments);
    }
}
