<?php

/**
 * Maps Mercure HTTP routes to closures that invoke the library's controllers.
 *
 * This file is a thin glue layer between an HTTP router and the
 * Jguillaumesio\PhpMercureHub library. It is intentionally framework-neutral:
 * each entry returns a closure so it can be plugged into any router
 * (FastRoute, Symfony, Slim, nikic/fast-route, plain PHP, ...).
 *
 * The library itself stays router-agnostic — it ships controller classes,
 * the SubscriptionManager singleton, and supporting models. How URLs reach
 * them is your choice.
 *
 * Usage (pseudo-code, see examples/SimpleRouter.php for a working demo):
 *
 *     $map = (new MercureEndpointMap())->build();
 *     $router->get('/.well-known/mercure',                   $map['subscription']);
 *     $router->post('/.well-known/mercure',                  $map['publish']);
 *     $router->get('/.well-known/mercure/subscriptions',     $map['subscriptionsAll']);
 *     $router->get('/.well-known/mercure/subscriptions/{topic}', $map['subscriptionsByTopic']);
 *     $router->get('/.well-known/mercure/subscriptions/{topic}/{subscriber}', $map['subscriptionDetail']);
 *
 * Returns for the subscriptions endpoints are associative arrays; encode them
 * as JSON for the Active Subscriptions API per the Mercure spec.
 */

declare(strict_types=1);

use Jguillaumesio\PhpMercureHub\Controllers\HubController;
use Jguillaumesio\PhpMercureHub\Controllers\SubscriptionController;

final class MercureEndpointMap
{
    /**
     * Return an associative array of `name => closure(...)` where each
     * closure accepts the route's captured path parameters and returns
     * a controller response.
     *
     * Subscription and publication handlers emit their own headers
     * (Link, Content-type, etc.) and write the body via echo.
     *
     * Active-subscriptions handlers return plain arrays; the caller is
     * responsible for JSON encoding.
     *
     * @return array<string, callable>
     */
    public function build(): array
    {
        return [
            'subscription' => static function (): void {
                (new HubController())->subscription();
            },

            'publish' => static function (): void {
                (new HubController())->publication();
            },

            'subscriptionsAll' => static function (): array {
                return (new SubscriptionController())->getAllSubscriptions();
            },

            'subscriptionsByTopic' => static function (array $params): array {
                return (new SubscriptionController())->getSubscriptionByTopicSelector($params['topic']);
            },

            'subscriptionDetail' => static function (array $params): ?array {
                return (new SubscriptionController())->getSubscriptionForTopic(
                    $params['topic'],
                    $params['subscriber']
                );
            },
        ];
    }
}
