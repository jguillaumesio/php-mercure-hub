<?php

namespace Jguillaumesio\PhpMercureHub\Authorization;

class HeadersAuthorization extends AbstractAuthorization  implements AuthorizationMethodInterface
{

    public function getJWT($request)
    {
        if (
            !\array_key_exists('headers', $request) ||
            !\array_key_exists('authorization', $request['headers']) ||
            \strpos((string) $request['headers']['authorization'], 'Bearer ') === false
        ) {
            return $this->next($request);
        }
        return \str_replace('Bearer ', '', $request['headers']['authorization']);
    }
}