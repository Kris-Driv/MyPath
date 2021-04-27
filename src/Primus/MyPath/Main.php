<?php

namespace Primus\MyPath;

use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\level\ChunkPopulateEvent;
use pocketmine\event\level\ChunkLoadEvent;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat;
use pocketmine\level\Level;
use Primus\MyPath\Protocol\PocketCoreAPI;
use Primus\MyPath\Protocol\PocketCoreClientThread;
use Primus\MyPath\Protocol\Request;
use Primus\MyPath\Protocol\Response;
use Primus\MyPath\Protocol\ThreadReaderTask;
use Primus\MyPath\Tasks\CreateFaceTask;

class Main extends PluginBase implements Listener
{
    use PocketCoreAPI;

    protected $thread;

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->startThread();
        $this->scheduleThreadReader();

        $this->ping();    
    }

    public function onDisable()
    {
        $this->stopThread();
    }

    private function scheduleThreadReader() 
    {
        $server = $this->getServer();

        $this->getScheduler()->scheduleRepeatingTask(new ThreadReaderTask($this->thread, function(Response $response) use ($server)  {
            $server->getLogger()->info("Got response: " . $response->type);

            $plugin = $server->getPluginManager()->getPlugin('MyPath');
            if($plugin instanceof Plugin && $plugin->isEnabled()) {
                $plugin->handleResponse($response);
            }
        }), 1);
    }

    private function startThread()
    {
        $this->thread = new PocketCoreClientThread('localhost', '27095', $this->getServer()->getLogger(), 2);
    }

    private function stopThread()
    {
        $this->thread->stop();
        $this->thread->quit();
    }

    public function createLayerAndSend($x, $z, $level = null)
    {
        $layer = $this->getTopLayer($x, $z, $level);

        $this->sendChunk($x, $z, $layer);
    }

    public function getTopLayer(int $x, int $z, ?Level $level = null): array
    {
        $level = $level ?? $this->getServer()->getDefaultLevel();
        $chunk = $level->getChunk($x, $z, true);
        $layer = [];

        $worldHeight = $level->getWorldHeight();
        for ($x = 0; $x < 16; $x++) {
            for ($z = 0; $z < 16; $z++) {

                // How many blocks we should ignore ...
                // $ignorance = 0;

                for ($y = $worldHeight; $y > 0; $y--) {
                    if (!in_array($blockId = $chunk->getBlockId($x, $y, $z), [0, 7])) {
                        // $ignorance--;
                        // if($ignorance >= 1) continue;

                        $layer[$x][$z][$y] = $blockId;

                        if ($blockId !== 9) {
                            break;
                        }
                        for ($yy = $y; $yy > 0; $yy--) {
                            if ($chunk->getBlockId($x, $yy, $z) !== 9) {
                                unset($layer[$x][$z][$y]);
                                $layer[$x][$z][$yy] = $blockId;
                                break;
                            }
                        }
                        break;
                    }
                }

                if (!isset($layer[$x][$z])) {
                    $layer[$x][$z][1] = 7; // put stone as default layer, change it to bedrock once you find out the id
                }
            }
        }

        return $layer;
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args): bool
    {
        switch (strtolower($command->getName())) {
            case 'ping':
                $this->ping($sender->getName());

                $sender->sendMessage('Pinging ...');
                break;

            case 'web':
                if (empty($args)) return false;

                // $this->sendData(['type' => 'command', 'payload' => implode(' ', $args)]);
                $this->sendMessage(implode(', ', $args), true);

                $sender->sendMessage('Message sent.');
                break;
        }
        return true;
    }

    /**
     * @priority MONITOR
     */
    public function playerJoin(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();

        $this->sendPlayerJoin($player);

        $this->getServer()->getAsyncPool()->submitTask(new CreateFaceTask($player->getId(), $player->getSkin()->getSkinData()));
    }

    /**
     * @priority MONITOR
     */
    public function playerLeave(PlayerQuitEvent $event)
    {
        $this->sendPlayerQuit($event->getPlayer(), $event->getQuitReason(), $event->getQuitMessage());
    }

    /**
     * @priority MONITOR
     */
    public function onMove(PlayerMoveEvent $event)
    {
        // if($event->getTo()->distance($event->getFrom()) < 0.3) return;

        $this->sendEntityPosition($event->getPlayer());
    }

    public function blockPlace(BlockPlaceEvent $event)
    {
        $block = $event->getBlock();
        $this->createLayerAndSend($block->getFloorX() >> 4, $block->getFloorZ() >> 4, $block->getLevel());
    }

    public function blockBreak(BlockBreakEvent $event)
    {
        $block = $event->getBlock();
        $this->createLayerAndSend($block->getFloorX() >> 4, $block->getFloorZ() >> 4, $block->getLevel());
    }

    public function chunkPopulate(ChunkPopulateEvent $event)
    {
        $chunk = $event->getChunk();

        $this->createLayerAndSend($chunk->getX(), $chunk->getZ(), $event->getLevel());
    }

    public function chunkLoaded(ChunkLoadEvent $event)
    {
        $chunk = $event->getChunk();

        $this->createLayerAndSend($chunk->getX(), $chunk->getZ(), $event->getLevel());
    }
}
