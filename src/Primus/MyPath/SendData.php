<?php

namespace Primus\MyPath;

use WebSocket\Client;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class SendData extends AsyncTask
{

    public $data;

    public function __construct(array $data)
    {
        $this->data = $data; 
    }

    public function onRun()
    {
        $client = new Client("ws://localhost:27095");

        $client->text(json_encode($this->data));

        $this->setResult($client->receive());
    }

    public function onCompletion(Server $server)
    {
        $server->getLogger()->info("Response: " . $this->getResult());
    }

}