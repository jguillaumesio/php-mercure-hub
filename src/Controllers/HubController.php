<?php

namespace Jguillaumesio\PhpMercureHub\Controllers;

use Jguillaumesio\PhpMercureHub\Authorization\AuthorizationManager;
use Jguillaumesio\PhpMercureHub\Models\Publication;
use Jguillaumesio\PhpMercureHub\SubscriptionManager;
use Jguillaumesio\PhpMercureHub\Utils\TopicUtils;
use Jguillaumesio\PhpMercureHub\Utils\UtilsManager;

class HubController {

    private $subscriptionManager;
    private $authManager;

    public function __construct(){
        $this->subscriptionManager = SubscriptionManager::getInstance();
        $this->authManager = AuthorizationManager::getInstance();
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

        $body = UtilsManager::getRequestBody();
        $topics = isset($body['topic']) ? (array) $body['topic'] : [];
        if(\count($topics) === 0){
            throw new \Error('INVALID_OR_MISSING_TOPIC');
        }

        $jwtPayload = $this->authManager->getJWTPayload($request);
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

        foreach($topics as $topicIri){
            $topic = TopicUtils::ensureTopic($topicIri, $this->subscriptionManager);
            new Publication(
                $topic,
                $data,
                $private,
                $id,
                $type,
                $retry
            );
        }

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
        $this->subscriptionManager->subscribe($topics);
        $this->subscriptionManager->setSubscriptionHeaders($topics);
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
