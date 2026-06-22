<?php

namespace Jguillaumesio\PhpMercureHub\Utils;

use Jguillaumesio\PhpMercureHub\Config;

class UtilsManager {

    private static $class;

    private static function getInstance(){
        if(self::$class === null){
            $config = Config::getConfig();
            self::$class = (isset($config['utils']) && \is_string($config['utils']) && \class_exists($config['utils'])) ? $config['utils'] : Utils::class;
        }
        return self::$class;
    }

    public static function __callStatic($method, $arguments) {
        return call_user_func_array([self::getInstance(), $method], $arguments);
    }
}
