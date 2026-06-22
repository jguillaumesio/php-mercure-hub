<?php

namespace Jguillaumesio\PhpMercureHub\Models;

use Jguillaumesio\PhpMercureHub\Utils\TopicUtils;

class Topic
{
    public $name;
    private $subscribers;
    private $publications;

    public function __construct($name){
        if(!TopicUtils::isValidTopicName($name)){
            throw new \Error('INVALID_EXISTS');
        }
        $this->name = $name;
        $this->subscribers = [];
        $this->publications = [];
    }

    public function addPublication($publication){
        $this->publications[] = $publication;
        \Jguillaumesio\PhpMercureHub\SubscriptionManager::getInstance()->registerPublication($publication->getId());
    }

    public function subscribe($subscriber = null){
        if (!\in_array($subscriber, $this->subscribers, true)) {
            $this->subscribers[] = $subscriber;
        }
    }

    public function getSubscription($subscriber){
        if(\in_array($subscriber, $this->subscribers, true)){
            return [
                'topic' => $this->name,
                'subscriber' => $subscriber->id
            ];
        }
        return null;
    }

    public function getSubscriptions(){
        return \array_map(fn($subscriber) => [
            'topic' => $this->name,
            'subscriber' => $subscriber->id
        ], $this->subscribers);
    }
}