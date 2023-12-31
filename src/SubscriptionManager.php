<?php

namespace Jguillaumesio\PhpMercureHub;

use Jguillaumesio\PhpMercureHub\Authorization\AuthorizationManager;
use Jguillaumesio\PhpMercureHub\Models\Subscriber;
use Jguillaumesio\PhpMercureHub\Models\Topic;
use Jguillaumesio\PhpMercureHub\Utils\TopicUtils;
use Jguillaumesio\PhpMercureHub\Utils\Utils;
use Jguillaumesio\PhpMercureHub\Utils\UtilsManager;

class SubscriptionManager
{
    private static $instance;
    private $topics = [];
    private $subscribers = [];
    private $request;
    private $hubUrl;

    public static function getInstance(){
        if(self::$instance === null){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getTopics() {
        return $this->topics;
    }

    public function setTopics(array $topics) {
        $this->topics = $topics;
    }

    public function getRequest() {
        return $this->request;
    }

    public function setRequest(array $request) {
        $this->request = $request;
    }

    public function getHubUrl() {
        return $this->hubUrl;
    }

    public function setHubUrl(string $hubUrl) {
        $this->hubUrl = $hubUrl;
    }

    public function __construct(){
        $this->request = [
            'headers' => UtilsManager::getHeaders(),
            'query_params' => UtilsManager::getQueryParams(),
            'cookies' => UtilsManager::getCookies()
        ];
        $this->processRequest();
        $this->hubUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/.well-known/mercure';
    }

    private function processRequest(){
        $this->request['response_type'] = $this->request['headers']['accept'] ?? $this->request['headers']['content_type'] ?? null;
        $this->request['language'] = $this->request['headers']['acceptlanguage'] ?? null;
    }

    public function addTopic($topicName){
        if(\is_array($this->topics)){
            $topic = new Topic($topicName);
            if(\array_key_exists($topicName, $this->topics)){
                throw new \Error('TOPIC_ALREADY_EXISTS');
            }
            $this->topics[$topicName] = $topic;
        }
    }

    public function getSubscriber($id){
        if (array_key_exists($id, $this->subscribers)) {
            return $this->subscribers[$id];
        } else {
            return null;
        }
    }

    public function subscribe($selector){
        $topics = TopicUtils::getMatchingTopics([$selector], $this->topics);
        if(\count($topics) > 0){
            $jwtPayload = (new AuthorizationManager())->getJWTPayload($this->request);
            $subscriber = $this->getSubscriber($jwtPayload['subscriber'] ?? null);
            if($subscriber === null){
                $this->subscribers[] = new Subscriber($topics);
            } else {
                $subscriber->subscribe($topics);
            }
            return true;
        }
        return false;
    }

    private function setResponseTypeHeader(){
        if($this->request['response_type'] === null){
            throw new \Error('MISSING_CONTENT_TYPE_OR_RESPONSE_TYPE');
        }
        else if(!array_key_exists($this->request['response_type'], UtilsManager::getAvailableResponseTypes())) {
            throw new \Error('INVALID_CONTENT_TYPE_OR_RESPONSE_TYPE');
        }
        UtilsManager::setHeader('Content-type', $this->request['response_type']);
    }

    private function setLinkHeaders($topics, $includeSelf = true){
        $headers = [
            ['key' => 'Link', 'value' => "<$this->hubUrl>; rel=\"mercure\""]
        ];
        foreach ($topics as $topic){
            if($includeSelf){
                $headers[] = ['key' => 'Link', 'value' => '<' . $topic->name . ($this->request['language'] !== null ? '-'.$this->request['language'] : '') . '.' . $this->request['response_type'] . '>; rel="self"'];
            }
        }
        UtilsManager::setHeaders($headers, false);
    }

    public function setSubscriptionHeaders($topics){
        $this->setLinkHeaders($topics);
        $this->setResponseTypeHeader();
    }

    public function setPublicationHeaders($topic){
        $this->setLinkHeaders($topic);
        $this->setResponseTypeHeader();
    }
}