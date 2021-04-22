<?php

namespace Primus\MyPath;

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
use WebSocket\ConnectionException;

class Main extends PluginBase implements Listener
{

    protected $browser;

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->browser = new Browser($this->getServer());
        $this->browser->ping();
        

        // for($x = 0; $x < 40; $x++) {
        //     for($z = 0; $z < 40; $z++) {
        //         $layer = $this->getTopLayer($x, $z);

        //         $this->browser->sendChunk($x, $z, $layer);
        //     }   
        // }
    }

    public function sendChunk($x, $z) {
        $layer = $this->getTopLayer($x, $z);

        $this->browser->sendChunk($x, $z, $layer);
    }

    public function getBrowser()
    {
        return $this->browser;
    }

    public function getTopLayer(int $x, $z): array
    {
        $level = $this->getServer()->getDefaultLevel();
        $chunk = $level->getChunk($x, $z, true);
        $layer = [];

        $worldHeight = $level->getWorldHeight();
        for ($x = 0; $x < 16; $x++) {
            for ($z = 0; $z < 16; $z++) {
                for ($y = $worldHeight; $y > 0; $y--) {
                    if (($blockId = $chunk->getBlockId($x, $y, $z)) !== 0) {
                        $layer[$x][$z][$y] = $blockId;

                        if($blockId !== 9) {
                            break;
                        }
                        for($yy = $y; $yy > 0; $yy--) {
                            if($chunk->getBlockId($x, $yy, $z) !== 9) {
                                unset($layer[$x][$z][$y]);
                                $layer[$x][$z][$yy] = $blockId;
                                break;
                            }
                        }
                        break;
                    }
                }
                if (!isset($layer[$x][$z][$y])) {
                    $layer[$x][$z][64] = 2; // put stone as default layer, change it to bedrock once you find out the id
                }
            }
        }

        return $layer;
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args): bool
    {
        switch (strtolower($command->getName())) {
            case 'ping':
                if(!$this->browser) {
                    $sender->sendMessage(TextFormat::RED . 'Browser interface not constructed');
                    return true;
                }

                $this->browser->ping($sender->getName());
                $sender->sendMessage('Pinging ...');
                break;
                
            case 'web':
                if (empty($args)) return false;

                // $this->sendData(['type' => 'command', 'payload' => implode(' ', $args)]);
                $this->browser->sendMessage(implode(', ', $args), true);

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
        $this->browser->sendPlayerJoin($event->getPlayer());
    }

    /**
     * @priority MONITOR
     */
    public function playerLeave(PlayerQuitEvent $event)
    {
        $this->browser->sendPlayerQuit($event->getPlayer(), $event->getQuitReason(), $event->getQuitMessage());
    }

    /**
     * @priority MONITOR
     */
    public function onMove(PlayerMoveEvent $event)
    {
        if($event->getTo()->distance($event->getFrom()) < 0.3) return;
        
        $this->browser->sendEntityPosition($event->getPlayer());
    }

    public function blockPlace(BlockPlaceEvent $event)
    {
        $block = $event->getBlock();
        $this->sendChunk($block->getFloorX() >> 4, $block->getFloorZ() >> 4);
    }

    public function blockBreak(BlockBreakEvent $event)
    {
        $block = $event->getBlock();
        $this->sendChunk($block->getFloorX() >> 4, $block->getFloorZ() >> 4);
    }

    public function chunkPopulate(ChunkPopulateEvent $event) 
    {
        $chunk = $event->getChunk();

        $this->sendChunk($chunk->getX(), $chunk->getZ());
    }

    public function chunkLoaded(ChunkLoadEvent $event) 
    {
        $chunk = $event->getChunk();

        $this->sendChunk($chunk->getX(), $chunk->getZ());
    }

}
