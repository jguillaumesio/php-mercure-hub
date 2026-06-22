<?php

namespace Jguillaumesio\PhpMercureHub\Models;

use Ramsey\Uuid\Uuid;

class Publication
{
    private $topic;
    private $data;
    private $private;
    private $id;
    private $type;
    private $retry;

    public function __construct($topic, $data = null, $private = null, $id = null, $type = null, $retry = null)
    {
        $id = ($id === null || !$this->isIdValid($id)) ? Uuid::uuid4() : $id;
        $this->topic = $topic;
        $this->id = $id;
        $this->type = $type;
        $this->retry = $retry;
        $this->private = $private;
        $this->data = $data;
        $topic->addPublication($this);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getData()
    {
        return $this->data;
    }

    public function isPrivate()
    {
        return $this->private;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getRetry()
    {
        return $this->retry;
    }

    public function getTopic()
    {
        return $this->topic;
    }

    private function isIdValid($id)
    {
        return $id[0] !== '#';
    }
}
