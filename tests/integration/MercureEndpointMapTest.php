<?php

namespace Jguillaumesio\PhpMercureHub\integration;

use PHPUnit\Framework\TestCase;

class MercureEndpointMapTest extends TestCase
{
    public function testBuildReturnsAllExpectedEntries()
    {
        require_once __DIR__ . '/../../examples/MercureEndpointMap.php';

        $map = (new \MercureEndpointMap())->build();

        $this->assertArrayHasKey('subscription', $map);
        $this->assertArrayHasKey('publish', $map);
        $this->assertArrayHasKey('subscriptionsAll', $map);
        $this->assertArrayHasKey('subscriptionsByTopic', $map);
        $this->assertArrayHasKey('subscriptionDetail', $map);

        $this->assertIsCallable($map['subscription']);
        $this->assertIsCallable($map['publish']);
        $this->assertIsCallable($map['subscriptionsAll']);
        $this->assertIsCallable($map['subscriptionsByTopic']);
        $this->assertIsCallable($map['subscriptionDetail']);
    }

    public function testSubscriptionDetailRejectsMissingTopic()
    {
        require_once __DIR__ . '/../../examples/MercureEndpointMap.php';

        $map = (new \MercureEndpointMap())->build();
        // Unknown topic + subscriber must return null (controller behavior).
        $result = $map['subscriptionDetail'](['topic' => '/no/such/topic', 'subscriber' => 'nope']);
        $this->assertNull($result);
    }
}
