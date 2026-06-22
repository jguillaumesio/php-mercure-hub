<?php

namespace Jguillaumesio\PhpMercureHub\Authorization;

use Jguillaumesio\PhpMercureHub\Config;

class CookiesAuthorization extends AbstractAuthorization implements AuthorizationMethodInterface
{

    public function getJWT($request)
    {
        $config = Config::getConfig();
       if(!\array_key_exists('auth_cookie_name', $config) || !\array_key_exists('cookies', $request) || !\array_key_exists($config['auth_cookie_name'], $request['cookies'])){
           return $this->next($request);
       }
       return $request['cookies'][$config['auth_cookie_name']];
    }
}
