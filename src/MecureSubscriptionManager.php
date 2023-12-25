<?php

namespace Jguillaumesio\PhpMercureHub;

use Jguillaumesio\PhpMercureHub\Utils\Utils;
use Rize\UriTemplate;

class MecureSubscriptionManager
{
    private $topics = [];
    private $request;
    private $utils;
    private $hubUrl;

    /**
     * @return array
     */
    public function getTopics() {
        return $this->topics;
    }

    /**
     * @param array $topics
     */
    public function setTopics(array $topics) {
        $this->topics = $topics;
    }

    /**
     * @return array
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * @param array $request
     */
    public function setRequest(array $request) {
        $this->request = $request;
    }

    /**
     * @return Utils|mixed
     */
    public function getUtils() {
        return $this->utils;
    }

    /**
     * @param Utils|mixed $utils
     */
    public function setUtils($utils) {
        $this->utils = $utils;
    }

    /**
     * @return string
     */
    public function getHubUrl() {
        return $this->hubUrl;
    }

    /**
     * @param string $hubUrl
     */
    public function setHubUrl(string $hubUrl) {
        $this->hubUrl = $hubUrl;
    }

    public function __construct(){
        $config = new Config();
        $this->utils = new $config['utils'] ?? new Utils();
        $this->request = [
            'headers' => $this->utils->getHeaders(),
            'query_params' => $this->utils->getQueryParams(),
            'cookies' => $this->utils->getCookies()
        ];
        $this->processRequest();
        $this->hubUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/.well-known/mercure';
    }

    private function processRequest(){
        $this->request['response_type'] = $this->request['headers']['accept'] ?? $this->request['headers']['content_type'] ?? null;
        $this->request['language'] = $this->request['headers']['accept-language'] ?? null;
    }

    public function getMatchingTopics($selector){
        if($selector === '*'){
            return $this->topics;
        }
        $uri = new UriTemplate($selector, ['version' => 4]);
        return \array_filter($this->topics, fn($topic) => $uri->extract($selector, $topic) !== null || $selector === $topic);
    }

    public function addTopic($topic){
        if(\is_array($this->topics)){
            if(\array_key_exists($topic->name, $this->topics)){
                throw new \Error('TOPIC_ALREADY_EXISTS');
            }
            $this->topics[$topic->name] = null;
        }
    }

    private function setResponseTypeHeader(){
        if($this->request['response_type'] === null){
            throw new \Error('MISSING_CONTENT_TYPE_OR_RESPONSE_TYPE');
        }
        else if(!array_key_exists($this->request['response_type'], $this->utils::$availableResponseTypes)) {
            throw new \Error('INVALID_CONTENT_TYPE_OR_RESPONSE_TYPE');
        }
        $this->utils->setHeader('Content-type', $this->request['response_type']);
    }

    private function setLinkHeaders($topic, $includeSelf = true){
        $headers = [
            ['key' => 'Link', 'value' => "<$this->hubUrl>; rel=\"mercure\""]
        ];
        if($includeSelf){
            $headers[] = ['key' => 'Link', 'value' => '<' . $topic . ($this->request['language'] !== null ? '-'.$this->request['language'] : '') . '.' . $this->request['response_type'] . '>; rel="self"'];
        }
        $this->utils->setHeaders($headers, false);
    }

    public function setSubscriptionHeaders($topic){
        $this->setLinkHeaders($topic);
        $this->setResponseTypeHeader();
    }

    public function setPublicationHeaders($topic){
        $this->setLinkHeaders($topic);
        $this->setResponseTypeHeader();
    }

    private function doesTopicExists($name){
        return \array_key_exists($name, $this->topics);
    }

    public function getTopic($name){
        return $this->doesTopicExists($name) ? $this->topics[$name] : null;
    }
}