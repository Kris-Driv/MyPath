<?php

namespace Primus\MyPath\Utils;

use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Server;

class Sector 
{

    public static function to($x, int $z = null, ?Level $level = null) {
        if($x instanceof Vector3) {
            if(!$level instanceof Level && $x instanceof Position) {
                $level = $x->getLevel();
            }
            $z = $x->getZ();
        }
        assert(!is_null($x) && !is_null($z), 'invalid coordinates given');
        assert($level instanceof Level, 'invalid level given');

        return new Position($x >> 8, 0, $z >> 8, $level);
    }

    public static function from(int $x, int $z, ?string $level = null) {
        return new Position($x >> 8, 0, $z >> 8, $level ? Server::getInstance()->getLevelByName($level) : null);
    }

}