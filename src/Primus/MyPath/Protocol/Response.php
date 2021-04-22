<?php

namespace Primus\MyPath\Protocol;

class Response {

    const TYPE_PING = 'ping';

    public $type;
    public $body;

    public $read = true;

    public function __construct(string $type, array $body, bool $read = true) {
        $this->type = $type;
        $this->body = $body;
        $this->read = $read;
    }

    public static function fromString(string $responseBody) {
        $decoded = json_decode($responseBody, true);
        if(!$decoded) {
            throw new \Exception('Could not decode the response: ' . $responseBody);
        }

        $type = $decoded['type'] ?? null;
        $body = $decoded['body'] ?? [];
        if(!$type) {
            throw new \Exception('Response type is not provided: ' . $responseBody);
        }
        
        $read = $decoded['read'] ?? true;
        unset($decoded['read']);
        
        return new Response($type, $body, $read);
    }

    public function get(string $key, $default = null) {
        return $this->body[$key] ?? $default;
    }

    public function __toString() {
        return json_encode(['type' => $this->type, 'body' => $this->body]);
    }

}