<?php

namespace Primus\MyPath\Tasks;

use pocketmine\Server;
use pocketmine\plugin\Plugin;
use Primus\MyPath\ServerProperties;
use Primus\MyPath\Protocol\Response;

class SendToBrowser extends WebSocketTask
{
    use ServerProperties;

    public function onRun()
    {
        $client = $this->client();
        $client->text($this->request->__toString());

        if($this->request->read) {
            $this->setResult(Response::fromString($client->receive()));
        } else {
            $this->setResult(true);
        }

        $client->close();
    }

    public function onCompletion(Server $server)
    {
        if($this->getResult() === true) return;

        $plugin = $server->getPluginManager()->getPlugin("MyPath");

        if($plugin instanceof Plugin && $plugin->isEnabled()) {
            $plugin->getBrowser()->handleResponse(Response::fromString($this->getResult()));
            return;
        }

        $server->getLogger()->warning('Unhandled response from browser');
    }

}