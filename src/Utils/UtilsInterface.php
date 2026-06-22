<?php

namespace Jguillaumesio\PhpMercureHub\Utils;

interface UtilsInterface{
    public static function setHeader($key, $value, $replace = true);
    public static function getHeaders();
    public static function getQueryParams();
    public static function getCookies();
}
