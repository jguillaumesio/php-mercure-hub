# Routing examples

This directory is **not part of the library API**. The
`Jguillaumesio\PhpMercureHub` library is router-agnostic: it ships
controllers, the `SubscriptionManager` singleton, and supporting models.
How HTTP requests reach them is your choice.

The scripts here exist as a copy-paste starting point and a working
demo. Use them as-is for local testing, replace them with the router
of your choice for production.

## Files

| File | Purpose |
|---|---|
| `MercureEndpointMap.php` | Glue: returns the close that each Mercure route should invoke. Front-controller-agnostic. |
| `SimpleRouter.php` | Tiny pure-PHP router. Reference implementation, no dependency cost. Replace with nikic/fast-route, Symfony, Slim, Laravel, etc. |
| `index.php` | Front controller used by `php -S` and tests. Wires SimpleRouter to the EndpointMap. |

## Wiring a different router

The EndpointMap returns a `string => callable` dict. To plug it into
another router:

```php
use Jguillaumesio\PhpMercureHub\Controllers\HubController;
use Jguillaumesio\PhpMercureHub\Controllers\SubscriptionController;

$map = (new MercureEndpointMap())->build();

$router->get('/.well-known/mercure', $map['subscription']);
$router->post('/.well-known/mercure', $map['publish']);
$router->get('/.well-known/mercure/subscriptions', $map['subscriptionsAll']);
$router->get('/.well-known/mercure/subscriptions/{topic}', $map['subscriptionsByTopic']);
$router->get('/.well-known/mercure/subscriptions/{topic}/{subscriber}', $map['subscriptionDetail']);
```

The library has no opinion on which router you use. The
`{topic}` and `{subscriber}` placeholders are RFC 6570 URI templates —
if your router emits them as plain path segments, that's fine.

## Running locally

```
MERCURE_CONFIG_PATH=$(pwd)/tests/fixtures/config.php \
    php -S localhost:8080 examples/index.php
```

You can then exercise the hub:

```
curl -N "http://localhost:8080/.well-known/mercure?topic=/books/1"
curl -X POST -d "topic=/books/1&data=hello" http://localhost:8080/.well-known/mercure
```
