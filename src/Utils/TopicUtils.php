<?php

namespace Jguillaumesio\PhpMercureHub\Utils;

use Jguillaumesio\PhpMercureHub\Models\Topic;
use Jguillaumesio\PhpMercureHub\SubscriptionManager;
use Rize\UriTemplate;

class TopicUtils {

    public static function getMatchingTopics($selectors, $topics){
        if(\in_array('*', $selectors, true)){
            return is_array($topics) ? array_values($topics) : [];
        }
        $result = [];
        $names = [];
        foreach ($selectors as $selector) {
            $uri = new UriTemplate($selector, ['version' => 4]);
            foreach ($topics as $topic) {
                $name = self::topicName($topic);
                if($name === null || isset($names[$name])){
                    continue;
                }
                if($uri->extract($selector, $name) !== null || $selector === $name){
                    $result[] = $topic;
                    $names[$name] = true;
                }
            }
        }
        return $result;
    }

    public static function getMatchingTopic($selectors, $topics){
        if(\in_array('*', $selectors, true)){
            $first = is_array($topics) ? reset($topics) : null;
            return $first === false ? null : $first;
        }
        foreach ($selectors as $selector) {
            $uri = new UriTemplate($selector, ['version' => 4]);
            foreach($topics as $topic){
                $name = self::topicName($topic);
                if($name === null){
                    continue;
                }
                if($uri->extract($selector, $name) !== null || $selector === $name){
                    return $topic;
                }
            }
        }
        return null;
    }

    public static function isValidTopicName($name){
        return \is_string($name) && \strlen($name);
    }

    /**
     * True if $topicIri matches any of the given selectors (each selector
     * may be a literal string or URI template per RFC 6570).
     */
    public static function isAuthorized(string $topicIri, array $selectors): bool{
        if(\in_array('*', $selectors, true)){
            return true;
        }
        foreach($selectors as $selector){
            if($selector === $topicIri){
                return true;
            }
            $uri = new UriTemplate($selector, ['version' => 4]);
            if($uri->extract($selector, $topicIri) !== null){
                return true;
            }
        }
        return false;
    }

    /**
     * Get or register a Topic object for the given IRI on the active
     * SubscriptionManager.
     */
    public static function ensureTopic(string $iri, SubscriptionManager $manager): Topic{
        return $manager->addTopic($iri);
    }

    /**
     * Normalize a topic-like value into its name string.
     */
    private static function topicName($topic): ?string{
        if(\is_object($topic)){
            return $topic->name ?? null;
        }
        if(\is_array($topic)){
            return $topic['name'] ?? null;
        }
        if(\is_string($topic)){
            return $topic;
        }
        return null;
    }
}
