<?php

namespace Jguillaumesio\PhpMercureHub\Response;

class HTMLMercureResponse implements MercureResponse
{

    public function generate($topic, $request)
    {
        $tmp = "<!doctype html>";
        /*
         * foreach ($values as $key => $value){
            $tmp .= "<title>$key: $value</title>";
        }
         */
        return $tmp;
    }
}