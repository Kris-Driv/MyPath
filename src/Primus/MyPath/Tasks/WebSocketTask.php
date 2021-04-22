<?php

namespace Primus\MyPath\Tasks;

use pocketmine\scheduler\AsyncTask;
use Primus\MyPath\ServerProperties;
use Primus\MyPath\Protocol\Request;
use Primus\MyPath\Browser;

abstract class WebSocketTask extends AsyncTask
{
    use ServerProperties;

    private $client;

    public $request = null;

    public $response = null;

    public function __construct(string $address, int $port, ?Request $request = null)
    {
        $this->address = 'ws://' . $address . ':' . $port;
        $this->port = $port;
        $this->request = $request;
    }

    protected function client() {
        if($this->client) {
            return $this->client;
        }

        $this->client = Browser::make($this->address);

        return $this->client;
    }

    public function setResult($result) {
        parent::setResult($result);
        
        if(is_string($result)) {
            $this->response = Response::fromString($result);
        }
    }

}