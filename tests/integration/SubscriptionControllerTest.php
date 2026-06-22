<?php

namespace Jguillaumesio\PhpMercureHub\integration;

use Jguillaumesio\PhpMercureHub\Controllers\SubscriptionController;
use Jguillaumesio\PhpMercureHub\Models\Topic;
use Jguillaumesio\PhpMercureHub\SubscriptionManager;
use PHPUnit\Framework\TestCase;

class SubscriptionControllerTest extends TestCase
{
    private $controller;
    private $manager;

    protected function setUp(): void
    {
        $this->manager = SubscriptionManager::getInstance();
        $this->manager->setTopics([]);
        $this->manager->setSubscribers([]);
        $this->controller = new SubscriptionController();
    }

    private function registerSubscriber(string $topicName): array
    {
        $topic = new Topic($topicName);
        $this->manager->setTopics([$topicName => $topic]);
        $subscriber = $this->manager->subscribe([$topic]);
        return [$topic, $subscriber];
    }

    public function testGetAllSubscriptionsEmpty()
    {
        $result = $this->controller->getAllSubscriptions();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetAllSubscriptionsWithData()
    {
        [$topic, $subscriber] = $this->registerSubscriber('https://example.com/foo');

        $result = $this->controller->getAllSubscriptions();
        $this->assertCount(1, $result);
        $this->assertSame('https://example.com/foo', $result[0]['topic']);
        $this->assertSame($subscriber->id, $result[0]['subscriber']);
    }

    public function testGetSubscriptionByTopicSelector()
    {
        [$topic, $subscriber] = $this->registerSubscriber('https://example.com/foo');

        $result = $this->controller->getSubscriptionByTopicSelector('https://example.com/foo');
        $this->assertCount(1, $result);
        $this->assertSame('https://example.com/foo', $result[0]['topic']);
        $this->assertSame($subscriber->id, $result[0]['subscriber']);
    }

    public function testGetSubscriptionForTopic()
    {
        [$topic, $subscriber] = $this->registerSubscriber('https://example.com/foo');

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
