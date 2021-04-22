<?php

namespace Primus\MyPath\Tasks;

use pocketmine\Server;
use pocketmine\plugin\Plugin;
use Primus\MyPath\ServerProperties;
use Primus\MyPath\Protocol\Response;
use WebSocket\ConnectionException;

class SendToBrowser extends WebSocketTask
{
    use ServerProperties;

    public function onRun()
    {
        $client = $this->client();
        
        try {

            $client->text($this->request->__toString());

        } catch(ConnectionException $e) {
            $this->setResult($e);

            return;
        }

        if($this->request->read) {
            $this->setResult(Response::fromString($client->receive()));
        } else {
            $this->setResult(true);
        }

        $client->close();
    }

    public function onCompletion(Server $server)
    {
        if(($result = $this->getResult()) === true) return;

        $plugin = $server->getPluginManager()->getPlugin("MyPath");

        if($result instanceof \Exception) {
            $server->getLogger()->error('PocketCore Error: ' . $result->getMessage());
            $server->getPluginManager()->disablePlugin($plugin);

            return;
        }


        if($plugin instanceof Plugin && $plugin->isEnabled()) {
            $plugin->getBrowser()->handleResponse(Response::fromString($this->getResult()));
            
            return;
        }

        $server->getLogger()->warning('Unhandled response from browser');
    }

}