<?php

namespace Jguillaumesio\PhpMercureHub\unit\Models;

use Jguillaumesio\PhpMercureHub\Models\Subscriber;
use Jguillaumesio\PhpMercureHub\Models\Topic;
use PHPUnit\Framework\TestCase;

class SubscriberTest extends TestCase
{
    public function testConstruct(){
        $topic = new Topic('https://example.com/a-topic');
        $subscriber = new Subscriber([$topic]);
        $this->assertNotEmpty($subscriber->id);
        // getSubscriptions returns non-empty when subscriber is registered
        $this->assertNotEmpty($topic->getSubscriptions());
    }

    public function testIdempotentSubscribe(){
        $topic = new Topic('https://example.com/a-topic');
        $subscriber = new Subscriber([$topic]);
        $subscriber->subscribe([$topic]);
        // Re-subscribing the same topic shouldn't create duplicate entries
        $this->assertCount(1, $topic->getSubscriptions());
    }
}
