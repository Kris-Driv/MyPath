<?php

namespace Primus\MyPath\Protocol;

use Primus\MyPath\ServerProperties;
use pocketmine\Thread;
use WebSocket\Client;
use WebSocket\ConnectionException;

class PocketCoreClientThread extends Thread 
{
    use ServerProperties;

    /** @var Client */
	protected $socket;

	/** @var \ThreadedLogger */
	protected $logger;

	/** @var bool */
	protected $stopped;

	/** @var \Volatile */
	protected $packetQueue;

    /** @var \Volatile */
    protected $responseQueue;

	/** @var string */
	private $timeout;

    /** @var callable */
    private $handler;

    public function __construct(string $address, int $port, \ThreadedLogger $logger, int $timeout = 2) {
        $this->logger = $logger;

        $this->stopped = false;
        $this->packetQueue = new \Volatile();
        $this->responseQueue = new \Volatile();

        $this->address = $address;
        $this->port = $port;
        $this->timeout = 2;

        $this->start(PTHREADS_INHERIT_NONE);
    }

    public function enqueuePacket(Request $packet) {
        $this->packetQueue[] = $packet;
    }

    public function run() {
        $this->registerClassLoader();

        $this->socket = new Client("ws://" . $this->address . ":" . $this->port, [
            'persistent' => true,
            'timeout' => $this->timeout,
        ]);

        while(!$this->stopped) {
            if($this->packetQueue->count() > 0){
                $packet = $this->packetQueue->pop();

                // $this->logger->info("Sending packet: \"" . $packet->type . "\"");

                try {
                    
                    $this->sendPacket($packet);

                    if($packet->read) {
                        $response = $this->receivePacket();
                        // $this->logger->info("Received response: \"" . $response->type . "\"");

                        $this->responseQueue[] = $response;
                    }

                } catch(ConnectionException $e) {
                    $this->logger->critical("ConnectionException: " . $e->getMessage());
                    $this->stop();

                    return;
                }
            }
        }

    }

    public function setHandler(callable $handler) {
        $this->handler = $handler;
    }

    private function sendPacket(Request $packet){
        $this->socket->text($packet->__toString());
    }

	private function receivePacket(): Response {
		return Response::fromString($this->socket->receive());
	}

	public function stop(){
		$this->stopped = true;
		$this->logger->info("Stopping PocketCore Client Thread...");
	}

}