<?php

namespace Primus\MyPath;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;

class Main extends PluginBase implements Listener 
{

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->sendData(['test' => 'value']);

        // $this->setupSocket();
    }

    public function sendData(array $data): void
    {
        $this->getServer()->getAsyncPool()->submitTask(new SendData($data));
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args): bool
    {
        if($command->getName() !== 'web') return true;
        if(empty($args)) return false;

        $this->sendData(['type' => 'command', 'payload' => implode(' ', $args)]);
        $sender->sendMessage('Message sent.');

        return true;
    }

    public function onMove(PlayerMoveEvent $event)
    {
        // $this->sendMovement($event->getPlayer());
    }

    private function setupSocket()
    {
        
    }

}