<?php

namespace Jguillaumesio\PhpMercureHub\unit\Models;

use Jguillaumesio\PhpMercureHub\Models\Topic;
use Jguillaumesio\PhpMercureHub\Models\Subscriber;
use PHPUnit\Framework\TestCase;

class TopicTest extends TestCase {

    public function testConstruct(){
        $topic = new Topic('https://example.com/a-topic');
        $this->assertSame('https://example.com/a-topic', $topic->name);
        $this->assertIsString($topic->name);
    }

    public function testEmptyNameRejected(){
        $this->expectException(\Error::class);
        new Topic('');
    }

    public function testGetSubscriptionsEmpty(){
        $topic = new Topic('https://example.com/a-topic');
        $this->assertIsArray($topic->getSubscriptions());
        $this->assertEmpty($topic->getSubscriptions());
    }
}
