<?php

namespace Jguillaumesio\PhpMercureHub\integration;

use Jguillaumesio\PhpMercureHub\Controllers\SubscriptionController;
use Jguillaumesio\PhpMercureHub\Models\Topic;
use Jguillaumesio\PhpMercureHub\Models\Subscriber;
use Jguillaumesio\PhpMercureHub\SubscriptionManager;
use PHPUnit\Framework\TestCase;

class SubscriptionControllerTest extends TestCase
{
    private $controller;
    private $manager;

    protected function setUp(): void
    {
        $this->manager = SubscriptionManager::getInstance();
        $this->controller = new SubscriptionController();
    }

    public function testGetAllSubscriptionsEmpty()
    {
        $this->manager->setTopics([]);
        $result = $this->controller->getAllSubscriptions();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetAllSubscriptionsWithData()
    {
        $topic = new Topic('https://example.com/foo');
        $subscriber = new Subscriber([$topic]);
        $this->manager->setTopics(['https://example.com/foo' => $topic]);

        $result = $this->controller->getAllSubscriptions();
        $this->assertCount(1, $result);
        $this->assertSame('https://example.com/foo', $result[0]['topic']);
        $this->assertSame($subscriber->id, $result[0]['subscriber']);
    }

    public function testGetSubscriptionByTopicSelector()
    {
        $topic = new Topic('https://example.com/foo');
        $subscriber = new Subscriber([$topic]);
        $this->manager->setTopics(['https://example.com/foo' => $topic]);

        $result = $this->controller->getSubscriptionByTopicSelector('https://example.com/foo');
        $this->assertCount(1, $result);
        $this->assertSame('https://example.com/foo', $result[0]['topic']);
    }

    public function testGetSubscriptionForTopic()
    {
        $topic = new Topic('https://example.com/foo');
        $subscriber = new Subscriber([$topic]);
        $this->manager->setTopics(['https://example.com/foo' => $topic]);

        $result = $this->controller->getSubscriptionForTopic(
            'https://example.com/foo',
            $subscriber->id
        );
        $this->assertNotNull($result);
        $this->assertSame('https://example.com/foo', $result['topic']);
        $this->assertSame($subscriber->id, $result['subscriber']);
    }

    public function testGetSubscriptionForTopicNotFound()
    {
        $result = $this->controller->getSubscriptionForTopic(
            'https://example.com/nonexistent',
            'nonexistent-id'
        );
        $this->assertNull($result);
    }
}
