<?php

namespace Jguillaumesio\PhpMercureHub;

use Jguillaumesio\PhpMercureHub\Authorization\AuthorizationManager;
use Jguillaumesio\PhpMercureHub\Models\Subscriber;
use Jguillaumesio\PhpMercureHub\Models\Topic;
use Jguillaumesio\PhpMercureHub\Utils\UtilsManager;

class SubscriptionManager
{
    private static $instance;
    private $topics = [];
    private $subscribers = [];
    private $publications = [];
    private $eventHistory = [];
    private $maxHistorySize = 100;
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

    public function getSubscribers(): array {
        return $this->subscribers;
    }

    public function setSubscribers(array $subscribers): void {
        $this->subscribers = $subscribers;
    }

    public function addTopic($topicName): Topic{
        if(\array_key_exists($topicName, $this->topics)){
            return $this->topics[$topicName];
        }
        $topic = new Topic($topicName);
        $this->topics[$topicName] = $topic;
        return $topic;
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

    public function registerPublication(string $id): void{
        $this->publications[] = $id;
    }

    public function getLastPublicationId(): ?string{
        if(\count($this->publications) === 0){
            return null;
        }
        return end($this->publications);
    }

    public function getMaxHistorySize(): int
    {
        return $this->maxHistorySize;
    }

    public function setMaxHistorySize(int $size): void
    {
        $this->maxHistorySize = max(0, $size);
    }

    /**
     * Record a publication in the event history. If the history exceeds
     * maxHistorySize, the oldest entry is evicted (FIFO).
     */
    public function addEventToHistory(string $eventId, \Jguillaumesio\PhpMercureHub\Models\Publication $publication): void
    {
        $this->eventHistory[$eventId] = $publication;
        while (\count($this->eventHistory) > $this->maxHistorySize) {
            array_shift($this->eventHistory);
        }
    }

    /**
     * Return events published strictly after the given event ID.
     * If $lastEventId is null or 'earliest', return all events.
     *
     * @return \Jguillaumesio\PhpMercureHub\Models\Publication[]
     */
    public function getEventsAfter(?string $lastEventId): array
    {
        if ($lastEventId === null || $lastEventId === 'earliest') {
            return array_values($this->eventHistory);
        }
        $result = [];
        $found = false;
        foreach ($this->eventHistory as $id => $pub) {
            if ($found) {
                $result[] = $pub;
            }
            if ($id === $lastEventId) {
                $found = true;
            }
        }
        return $result;
    }

    /**
     * Identifier of the last published event, or null if none yet.
     */
    public function getLastEventID(): ?string
    {
        if (empty($this->eventHistory)) {
            return null;
        }
        return array_key_last($this->eventHistory);
    }

    public function __construct(){
        $this->request = [
            'headers' => UtilsManager::getHeaders(),
            'query_params' => UtilsManager::getQueryParams(),
            'cookies' => UtilsManager::getCookies()
        ];
        $this->processRequest();
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $this->hubUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $host . '/.well-known/mercure';
    }

    private function processRequest(){
        $this->request['response_type'] = $this->request['headers']['accept'] ?? $this->request['headers']['content_type'] ?? null;
        $this->request['language'] = $this->request['headers']['acceptlanguage'] ?? null;
    }

    public function getSubscriber($id){
        if (\array_key_exists($id, $this->subscribers)) {
            return $this->subscribers[$id];
        } else {
            return null;
        }
    }

    /**
     * Register a subscription for the (anonymous or JWT-identified)
     * subscriber for the topics matching the request topic selectors.
     */
    public function subscribe(array $topics): Subscriber{
        $jwtPayload = (new AuthorizationManager())->getJWTPayload($this->request);
        $subscriberId = (is_array($jwtPayload) && isset($jwtPayload['sub'])) ? $jwtPayload['sub'] : null;

        if($subscriberId !== null && $this->getSubscriber($subscriberId) !== null){
            $subscriber = $this->getSubscriber($subscriberId);
        } else {
            $subscriber = new Subscriber($topics);
            // Stable key required so getSubscriber($id) can resolve later.
            $this->subscribers[$subscriber->id] = $subscriber;
        }
        $subscriber->subscribe($topics);
        return $subscriber;
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
        if(!is_array($topics)){
            $topics = [$topics];
        }
        $headers = [
            ['key' => 'Link', 'value' => "<$this->hubUrl>; rel=\"mercure\""]
        ];
        foreach ($topics as $topic){
            if(!$topic instanceof \Jguillaumesio\PhpMercureHub\Models\Topic){
                continue;
            }
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
        $this->setLinkHeaders([$topic]);
        $this->setResponseTypeHeader();
    }
}
