<?php

/**
 * Front controller for the Mercure hub example.
 *
 * Wires SimpleRouter to MercureEndpointMap. Replace SimpleRouter with
 * your framework's router and keep the EndpointMap; that's the only
 * contract this library needs from you.
 *
 * Run with PHP's built-in server:
 *
 *     MERCURE_CONFIG_PATH=/path/to/config.php php -S localhost:8080 examples/index.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/SimpleRouter.php';
require __DIR__ . '/MercureEndpointMap.php';

$map = (new MercureEndpointMap())->build();
$router = new SimpleRouter();

// The active subscriptions API returns application/ld+json per the Mercure spec.
$json = static function (callable $handler): callable {
    return static function ($params) use ($handler) {
        $result = $handler($params);
        header('Content-Type: application/ld+json');
        echo $result === null ? '' : json_encode($result);
    };
};

// Subscriptions endpoints — wrap in JSON encoder
$router->get('/.well-known/mercure/subscriptions', $json($map['subscriptionsAll']));
$router->get('/.well-known/mercure/subscriptions/{topic}', $json($map['subscriptionsByTopic']));
$router->get('/.well-known/mercure/subscriptions/{topic}/{subscriber}', $json($map['subscriptionDetail']));

// SSE + publish — handlers emit headers and write to output themselves
$router->get('/.well-known/mercure', $map['subscription']);
$router->post('/.well-known/mercure', $map['publish']);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if (!$router->dispatch($method, $path)) {
        http_response_code(404);
        echo 'Not found';
    }
} catch (\Throwable $e) {
    $status = match ($e->getMessage()) {
        'INVALID_OR_MISSING_AUTHORIZATION',
        'MISSING_TOPIC_AUTHORIZATION' => 401,
        'INVALID_CONTENT_TYPE',
        'INVALID_OR_MISSING_TOPIC',
        'INVALID_ID' => 400,
        default => 500,
    };
    http_response_code($status);
    echo $e->getMessage();
}
