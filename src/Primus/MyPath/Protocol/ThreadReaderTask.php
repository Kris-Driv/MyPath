<?php

namespace Primus\MyPath\Protocol;

use pocketmine\scheduler\Task;

class ThreadReaderTask extends Task 
{

    private $thread;

    private $handler;

    public function __construct(PocketCoreClientThread $thread, callable $handler) {
        $this->thread = $thread;
        $this->handler = $handler;
    }

    public function onRun(int $currentTick): void
    {

        while($this->thread->responseQueue->count() > 0) {
            $response = $this->thread->responseQueue->pop();

            call_user_func($this->handler, $response);
        }

    }

}