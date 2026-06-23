<?php

namespace Jguillaumesio\PhpMercureHub\Controllers;

use Jguillaumesio\PhpMercureHub\Authorization\AuthorizationManager;
use Jguillaumesio\PhpMercureHub\Models\Publication;
use Jguillaumesio\PhpMercureHub\SubscriptionManager;
use Jguillaumesio\PhpMercureHub\Utils\SSE;
use Jguillaumesio\PhpMercureHub\Utils\TopicUtils;
use Jguillaumesio\PhpMercureHub\Utils\UtilsManager;

class HubController {

    private $subscriptionManager;

    public function __construct(){
        $this->subscriptionManager = SubscriptionManager::getInstance();
    }

    /**
     * POST /.well-known/mercure
     * Handles the publication of an update by an authorized publisher.
     */
    public function publication(){
        $request = $this->subscriptionManager->getRequest();
        $headers = $request['headers'];
        if(!\is_array($headers) || !\array_key_exists('contenttype', $headers) || $headers['contenttype'] !== 'application/x-www-form-urlencoded'){
            throw new \Error('INVALID_CONTENT_TYPE');
        }

        // CSRF mitigation: when the request has an Origin or Referer header,
        // verify it matches our host. This blocks cross-origin form POSTs
        // from other domains (Mercure RFC Security Considerations).
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $origin = $headers['origin'] ?? null;
        $referer = $headers['referer'] ?? null;
        if ($origin !== null && parse_url($origin, PHP_URL_HOST) !== $host) {
            throw new \Error('INVALID_OR_MISSING_AUTHORIZATION');
        }
        if ($origin === null && $referer !== null && parse_url($referer, PHP_URL_HOST) !== $host) {
            throw new \Error('INVALID_OR_MISSING_AUTHORIZATION');
        }

        $body = UtilsManager::getRequestBody();
        $topics = isset($body['topic']) ? (array) $body['topic'] : [];
        if(\count($topics) === 0){
            throw new \Error('INVALID_OR_MISSING_TOPIC');
        }

        $jwtPayload = AuthorizationManager::getInstance()->getJWTPayload($request);
        if($jwtPayload === null){
            throw new \Error('INVALID_OR_MISSING_AUTHORIZATION');
        }
        $mercureClaim = $jwtPayload['mercure'] ?? null;
        $publishSelectors = $mercureClaim['publish'] ?? null;
        if(!\is_array($publishSelectors)){
            throw new \Error('INVALID_OR_MISSING_AUTHORIZATION');
        }

        // Authorization: every POSTed topic must be covered by at least one
        // selector from the publish claim (URI template matching per RFC 6570).
        foreach($topics as $topic){
            if(!TopicUtils::isAuthorized($topic, $publishSelectors)){
                throw new \Error('MISSING_TOPIC_AUTHORIZATION');
            }
        }

        // The registered Topic model objects are looked up by IRI so the
        // dispatched Publication can be attached to them. Create a stub Topic
        // wrapper around each post body item to satisfy the model contract.
        $private = !empty($body['private']);
        $id = $body['id'] ?? null;
        $type = $body['type'] ?? null;
        $retry = isset($body['retry']) ? (int) $body['retry'] : null;
        $data = $body['data'] ?? null;

        // First topic IRI is canonical, remaining are alternates per Mercure spec.
        $canonicalIri = $topics[0];
        $alternateIris = array_slice($topics, 1);
        $canonicalTopic = TopicUtils::ensureTopic($canonicalIri, $this->subscriptionManager);
        $alternateTopics = array_map(
            fn($iri) => TopicUtils::ensureTopic($iri, $this->subscriptionManager),
            $alternateIris
        );
        new Publication(
            $canonicalTopic,
            $data,
            $private,
            $id,
            $type,
            $retry,
            $alternateTopics
        );

        UtilsManager::setHeader('Content-type', 'text/plain');
        echo $this->subscriptionManager->getLastPublicationId();
    }

    public function subscription(){
        $request = $this->subscriptionManager->getRequest();
        $selectors = !\array_key_exists('topic', $request['query_params']) ? [] : (array) $request['query_params']['topic'];
        if(\count($selectors) === 0){
            throw new \Error('INVALID_OR_MISSING_TOPIC');
        }
        $topics = TopicUtils::getMatchingTopics($selectors, $this->subscriptionManager->getTopics());
        if(\count($topics) === 0){
            throw new \Error('INVALID_OR_MISSING_TOPIC');
        }

        // Resolve subscriber identity from JWT (sub claim) when available so
        // reconnections land on the same subscriber slot in the manager.
        $jwtPayload = AuthorizationManager::getInstance()->getJWTPayload($request);
        $subscriberId = (is_array($jwtPayload) && isset($jwtPayload['sub'])) ? $jwtPayload['sub'] : null;
        $mercureClaim = is_array($jwtPayload) ? ($jwtPayload['mercure'] ?? null) : null;
        $subscribeSelectors = (is_array($mercureClaim) && isset($mercureClaim['subscribe']) && is_array($mercureClaim['subscribe']))
            ? $mercureClaim['subscribe'] : [];

        // Last-Event-ID header takes precedence per Mercure RFC; query param
        // mirrors the JSON-LD lastEventID convention.
        $lastEventId = $request['headers']['last-event-id']
            ?? $request['query_params']['lastEventID']
            ?? null;

        // Register subscriber + set Link headers.
        $this->subscriptionManager->subscribe($topics, $subscriberId);
        $this->subscriptionManager->setSubscriptionHeaders($topics);

        // SSE response headers + disable buffering.
        SSE::initHeaders();

        // Echo the Last-Event-ID the client provided so it always knows where
        // the next replay starts (MUST per spec).
        header('Last-Event-ID: ' . ($lastEventId ?? 'earliest'));

        // Replay history strictly newer than the last event the client saw.
        $history = $this->subscriptionManager->getEventsAfter($lastEventId);
        foreach ($history as $publication) {
            $this->dispatchPublication($publication, $topics, $subscribeSelectors, $subscriberId);
        }

        // JWT exp claim: if set, close the stream at $exp.
        $exp = (is_array($jwtPayload) && isset($jwtPayload['exp'])) ? (int) $jwtPayload['exp'] : null;
        $lastSentId = $this->subscriptionManager->getLastEventID();
        $lastSendAt = time();
        $keepAliveSeconds = 30;

        // Live loop. PHP's lack of non-blocking pubsub means we poll the
        // history tail at 200ms intervals and emit any new events.
        while (true) {
            // JWT expiry
            if ($exp !== null && time() >= $exp) {
                SSE::emit('', 'jwt-expired');
                break;
            }

            $currentLastId = $this->subscriptionManager->getLastEventID();
            if ($currentLastId !== $lastSentId) {
                $newEvents = $this->subscriptionManager->getEventsAfter($lastSentId);
                foreach ($newEvents as $publication) {
                    $this->dispatchPublication($publication, $topics, $subscribeSelectors, $subscriberId);
                    $lastSentId = $publication->getId();
                }
                $lastSendAt = time();
            }

            // Periodic keep-alive so proxies don't time the connection out.
            if (time() - $lastSendAt >= $keepAliveSeconds) {
                SSE::emit('', 'keep-alive');
                $lastSendAt = time();
            }

            // Break the connection if the underlying stream was reset.
            if (connection_aborted()) {
                break;
            }

            usleep(200000); // 200ms poll
        }
    }

    /**
     * Dispatch a single publication to the SSE stream when it matches the
     * subscribed topics and (for private updates) the subscriber's
     * authorized topic selectors.
     */
    private function dispatchPublication(
        Publication $publication,
        array $subscribedTopics,
        array $subscribeSelectors,
        ?string $subscriberId
    ): void {
        // Match by topic name (canonical OR alternate).
        $pubTopics = $publication->getAllTopics();
        $matches = false;
        foreach ($pubTopics as $pubTopic) {
            $pubName = is_string($pubTopic) ? $pubTopic : ($pubTopic->name ?? null);
            if ($pubName === null) {
                continue;
            }
            foreach ($subscribedTopics as $subTopic) {
                $subName = is_string($subTopic) ? $subTopic : ($subTopic->name ?? null);
                if ($pubName === $subName) {
                    $matches = true;
                    break 2;
                }
            }
        }
        if (!$matches) {
            return;
        }

        // Private updates require the subscriber's subscribe selectors to
        // authorize the topic (canonical or alternate).
        if ($publication->isPrivate()) {
            if ($subscriberId === null || empty($subscribeSelectors)) {
                return;
            }
            $authorized = false;
            foreach ($pubTopics as $pubTopic) {
                $pubName = is_string($pubTopic) ? $pubTopic : ($pubTopic->name ?? null);
                if ($pubName !== null && TopicUtils::isAuthorized($pubName, $subscribeSelectors)) {
                    $authorized = true;
                    break;
                }
            }
            if (!$authorized) {
                return;
            }
        }

        // Serialize data: payload if array/object, otherwise raw string.
        $data = $publication->getData();
        if (is_array($data) || is_object($data)) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        SSE::emit(
            (string) ($data ?? ''),
            $publication->getId(),
            $publication->getType() !== null ? $publication->getType() : 'message',
            $publication->getRetry() !== null ? (int) $publication->getRetry() : null
        );
    }

    public function discovery(){
        //TODO https://mercure.rocks/spec#discovery
        /*
         * TODO
         * The cookie SHOULD be set during discovery (see discovery) to improve the overall security. both the publisher and the hub have to share the same second level domain. The Domain attribute MAY be used to allow the publisher and the hub to use different subdomains. See discovery.
         * The cookie SHOULD have the Secure, HttpOnly and SameSite attributes set. The cookie's Path attribute SHOULD also be set to the hub's URL. See security considerations.
         */
    }

    public function topic($url){
        $topicName = "/$url";
        $topic = TopicUtils::getMatchingTopic([$topicName], $this->subscriptionManager->getTopics());
        if($topic === null){
            throw new \Error('INVALID_OR_MISSING_TOPIC');
        }
        $this->subscriptionManager->setSubscriptionHeaders([$topic]);
        echo UtilsManager::generateResponse($topic, $this->subscriptionManager->getRequest());
    }
}
