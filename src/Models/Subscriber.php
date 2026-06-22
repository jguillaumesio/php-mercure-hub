<?php

namespace Jguillaumesio\PhpMercureHub\Models;

use Ramsey\Uuid\Uuid;

class Subscriber {

    public $id;
    private $subscribedTopics = [];

    public function __construct($topics){
        $this->id = Uuid::uuid4()->toString();
        $this->subscribe($topics);
    }

    public function subscribe($topics){
        foreach($topics as $topic){
            if (!in_array($topic, $this->subscribedTopics, true)) {
                $topic->subscribe($this);
                $this->subscribedTopics[] = $topic;
            }
        }
    }

}