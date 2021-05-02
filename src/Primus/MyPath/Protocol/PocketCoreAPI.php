<?php

namespace Primus\MyPath\Protocol;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\entity\Entity;

use Primus\MyPath\Protocol\Request;
use Primus\MyPath\Protocol\Response;

trait PocketCoreAPI {

    protected $server;

    protected $ping;

    public function handleResponse(Response $response) : void {
        $this->getServer()->getLogger()->info('Got response: ' . $response);

        try {
            switch($response->type) {
                case 'ping':
                    $this->handlePingResponse($response->get('time'));
                    break;
                case 'login.server':
                    if($response->body['status'] !== true) {
                        $this->getLogger()->error('Authentication with supervisor proxy server failed.');
                        $this->getServer()->getPluginManager()->disablePlugin($this);
                    } else {
                        $this->getLogger()->info('Connected to Supervisor proxy server successfully!');
                    }
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
        $this->thread->enqueuePacket($request);
    }

    public function ping(): void {
        $this->sendRequest(Request::ping());
    }

    public function sendPlayerMessage(int $eid, string $message) {
        $this->sendRequest(new Request('player.message', [
            'message' => $message,
            'eid' => $eid,
        ], false));
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

    public function login(Server $server) {
        $this->sendRequest(new Request('login.server', [
            'levels' => ['rblock'], // TODO
            'name' => $server->getName(),
            'ip' => $server->getIp(),
            'port' => $server->getPort()
        ], true));
    }

}