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
        $subscriptions = \array_reduce(
            $this->subscriptionManager->getTopics(),
            fn($acc, $topic) => [...$acc, ...$topic->getSubscriptions()],
            []
        );
        return [
            '@context' => 'https://mercure.rocks/',
            'id' => '/.well-known/mercure/subscriptions',
            'type' => 'Subscriptions',
            'lastEventID' => $this->subscriptionManager->getLastEventID() ?? 'earliest',
            'subscriptions' => $subscriptions,
        ];
    }

    public function getSubscriptionByTopicSelector($selector){
        $topics = TopicUtils::getMatchingTopics([$selector], $this->subscriptionManager->getTopics());
        $subscriptions = \array_reduce(
            $topics,
            fn($acc, $topic) => [...$acc, ...$topic->getSubscriptions()],
            []
        );
        return [
            '@context' => 'https://mercure.rocks/',
            'id' => '/.well-known/mercure/subscriptions/' . rawurlencode($selector),
            'type' => 'Subscriptions',
            'lastEventID' => $this->subscriptionManager->getLastEventID() ?? 'earliest',
            'subscriptions' => $subscriptions,
        ];
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
        $sub = $topics[0]->getSubscription($subscriber);
        if ($sub === null) {
            return null;
        }
        return [
            '@context' => 'https://mercure.rocks/',
            'id' => '/.well-known/mercure/subscriptions/' . rawurlencode($topicName) . '/' . rawurlencode($subscriberId),
            'type' => 'Subscription',
            'topic' => $sub['topic'],
            'subscriber' => $sub['subscriber'],
            'active' => true,
            'lastEventID' => $this->subscriptionManager->getLastEventID() ?? 'earliest',
        ];
    }
}