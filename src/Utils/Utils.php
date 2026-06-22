<?php

namespace Jguillaumesio\PhpMercureHub\Utils;

class Utils extends AbstractUtils implements UtilsInterface {

    public static function setHeader($key, $value, $replace = true){
        if(\headers_sent()){
            throw new \Error('HEADERS_ALREADY_SENT');
        }
        header("$key: $value", $replace);
    }

    public static function setHeaders($keyValues, $replace){
        //This allow to only check one time for headers_sent instead or n times
        if(\headers_sent()){
            throw new \Error('HEADERS_ALREADY_SENT');
        }
        foreach (\array_filter($keyValues, fn($e) => \array_key_exists('key', $e) && \array_key_exists('value', $e)) as $e){
            header("{$e['key']}: {$e['value']}", $replace);
        }
    }

    public static function getHeaders(){
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headers[strtolower(str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower(substr($key, 5))))))] = $value;
            }
        }
        return $headers;
    }

    public static function getQueryParams(){
        if(!\array_key_exists('QUERY_STRING', $_SERVER) || $_SERVER['QUERY_STRING'] === ''){
            return [];
        }
        $queryParams = [];
        foreach (explode('&', $_SERVER['QUERY_STRING']) as $param) {
            list($name, $value) = array_pad(explode('=', $param, 2), 2, null);
            $name = urldecode($name);
            $value = urldecode($value);
            if (array_key_exists($name, $queryParams)) {
                if (!is_array($queryParams[$name])) {
                    $queryParams[$name] = [$queryParams[$name]];
                }
                $queryParams[$name][] = $value;
            } else {
                $queryParams[$name] = $value;
            }
        }
        return $queryParams;
    }

    public static function getCookies(){
        $out = [];
        if(!isset($_SERVER['HTTP_COOKIE']) || $_SERVER['HTTP_COOKIE'] === ''){
            return $out;
        }
        foreach (explode(';', $_SERVER['HTTP_COOKIE']) as $pair) {
            $pair = trim($pair);
            if($pair === ''){
                continue;
            }
            list($name, $value) = array_pad(explode('=', $pair, 2), 2, null);
            $out[urldecode($name)] = urldecode($value);
        }
        return $out;
    }

    /**
     * Parse application/x-www-form-urlencoded request bodies.
     * Supports repeated keys (returned as array values).
     */
    public static function getRequestBody(){
        $body = [];
        $raw = file_get_contents('php://input');
        if($raw === false || $raw === ''){
            return $body;
        }
        foreach (explode('&', $raw) as $pair) {
            if($pair === ''){
                continue;
            }
            list($name, $value) = array_pad(explode('=', $pair, 2), 2, null);
            $name = urldecode($name);
            $value = urldecode($value);
            if (\array_key_exists($name, $body)) {
                if (!\is_array($body[$name])) {
                    $body[$name] = [$body[$name]];
                }
                $body[$name][] = $value;
            } else {
                $body[$name] = $value;
            }
        }
        return $body;
    }
}
