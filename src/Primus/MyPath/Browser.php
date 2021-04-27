<?php

namespace Primus\MyPath;

use WebSocket\Client;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\entity\Entity;

use Primus\MyPath\Tasks\SendToBrowser;
use Primus\MyPath\Protocol\Request;
use Primus\MyPath\Protocol\Response;

class Browser {
    use ServerProperties;

    protected $server;

    protected $ping;

    public function __construct(Server $server, string $address = 'localhost', int $port = 27095) {
        $this->server = $server;
        $this->address = $address;
        $this->port = $port;
    }

    public function getServer(): Server {
        return $this->server;
    }

    public static function make(string $address, int $port) : Client {
        return new Client("ws://$address:$port");
    }

    public function handleResponse(Response $response) : void {
        // $this->getServer()->getLogger()->info('Got response: ' . $response);

        try {
            switch($response->type) {
                case 'ping':
                    $this->handlePingResponse($response->get('time'));
                    break;
                default:
                    $this->getServer()->getLogger()->info('Unhandled response type: ' . $response->type . ' ');
                    break;
            }
        } catch (\Exception $e) {
            $this->getServer()->getLogger()->warning('Error while handling response: ' . $e->getMessage());
            echo $e->getTraceAsString() . PHP_EOL;
        }
    }

    public function handlePingResponse(int $timestamp) : void {
        $this->ping = microtime(true) - $timestamp;

        $this->getServer()->getLogger()->info("Ping: " . floor($this->ping*1000) . " ms");
    }

    public function sendRequest(Request $request): void {
        $this->getServer()->getAsyncPool()->submitTask(new SendToBrowser($this->address, $this->port, $request));
    }

    public function ping(): void {
        $this->sendRequest(Request::ping());
    }
    
    public function sendMessage(string $message, bool $broadcast = true) {
        $this->sendRequest(new Request('message', [
            'message' => $message,
            'broadcast' => $broadcast
        ], false));
    }

    public function sendPlayerJoin(Player $player) {
        $this->sendRequest(new Request('player.join', [
            'eid' => $player->getId(), # TODO
            'name' => $player->getDisplayName(),
            'position' => [
                'x' => $player->getFloorX(),
                'y' => $player->getFloorY(),
                'z' => $player->getFloorZ(),
                'yaw' => $player->getYaw(),
                'pitch' => $player->getPitch()
            ]
        ], false));
    }

    public function sendPlayerFace(int $eid, string $pixelArray) {
        $this->sendRequest(new Request('player.face', [
            'eid' => $eid,
            'pixelArray' => $pixelArray
        ], false));
    }

    public function sendPlayerQuit(Player $player, string $reason, string $message) {
        $this->sendRequest(new Request('player.leave', [
            'eid' => $player->getId(), # TODO
            'name' => $player->getDisplayName(),
            'reason' => $reason,
            'message' => $message
        ], false));
    }

    public function sendEntityPosition(Entity $entity) {
        $this->sendRequest(new Request('entity.position', [
            'eid' => $entity->getId(), # TODO
            'position' => [
                'x' => $entity->getFloorX(),
                'y' => $entity->getFloorY(),
                'z' => $entity->getFloorZ(),
                'yaw' => $entity->getYaw(),
                'pitch' => $entity->getPitch()
            ],
        ], false));
    }

    public function sendChunk(int $x, int $z, array $layer) {
        $this->sendRequest(new Request('chunk', [
            'chunk' => [
                'x' => $x,
                'z' => $z,
                'layer' => $layer
            ]
        ], false));
    }

}