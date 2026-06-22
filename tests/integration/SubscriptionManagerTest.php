<?php

namespace Jguillaumesio\PhpMercureHub\integration;

use Jguillaumesio\PhpMercureHub\SubscriptionManager;
use PHPUnit\Framework\TestCase;

class SubscriptionManagerTest extends TestCase {

    private $subscriptionManager;

    protected function setUp(): void {
        $this->subscriptionManager = new SubscriptionManager();
    }

    public function testGettersAndSetters()
    {
        $topics = ['topic1', 'topic2'];
        $this->subscriptionManager->setTopics($topics);
        $this->assertEquals($topics, $this->subscriptionManager->getTopics());

        $request = ['headers' => ['accept' => 'application/json']];
        $this->subscriptionManager->setRequest($request);
        $this->assertEquals($request, $this->subscriptionManager->getRequest());

        $hubUrl = 'https://example.com/.well-known/mercure';
        $this->subscriptionManager->setHubUrl($hubUrl);
        $this->assertEquals($hubUrl, $this->subscriptionManager->getHubUrl());
    }
}
