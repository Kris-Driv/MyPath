<?php

namespace Primus\MyPath\Protocol;

class Request extends Response {

    public static function ping(): self {
        return new self('ping', ['time' => microtime(true)], true);
    }

}