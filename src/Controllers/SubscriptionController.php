<?php

namespace Jguillaumesio\PhpMercureHub\Controllers;

use Jguillaumesio\PhpMercureHub\SubscriptionManager;
use Jguillaumesio\PhpMercureHub\Utils\TopicUtils;

class SubscriptionController
{

    private $subscriptionManager;

    public function __construct(){
        $this->subscriptionManager = SubscriptionManager::getInstance();
    }

    public function getAllSubscriptions(){
        return \array_reduce($this->subscriptionManager->getTopics(), fn($acc, $topic) => [...$acc, ...$topic->getSubscriptions()], []);
    }

    public function getSubscriptionByTopicSelector($selector){
        $topics = TopicUtils::getMatchingTopics([$selector], $this->subscriptionManager->getTopics());
        return \array_reduce($topics, fn($acc, $topic) => [...$acc, ...$topic->getSubscriptions()], []);
    }

    public function getSubscriptionForTopic($topicName, $subscriberId){
        $subscriber = $this->subscriptionManager->getSubscriber($subscriberId);
        if ($subscriber === null) {
            return null;
        }
        $topics = TopicUtils::getMatchingTopics([$topicName], $this->subscriptionManager->getTopics());
        if(\count($topics) !== 1){
            return null;
        }
        return $topics[0]->getSubscription($subscriber);
    }
}