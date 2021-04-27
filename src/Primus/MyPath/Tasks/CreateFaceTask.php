<?php

namespace Primus\MyPath\Tasks;

use pocketmine\scheduler\AsyncTask;
use Primus\MyPath\Browser;
use pocketmine\Server;

class CreateFaceTask extends AsyncTask
{

    public $eid, $skinData;

    public function __construct(int $eid, string $skinData)
    {
        $this->eid = $eid;
        $this->skinData = $skinData;
    }

    public function onRun()
    {
        $strArray = [];

        switch (strlen($this->skinData)) {
            case 8192:
            case 16384:
                $maxX = $maxY = 8;

                $width = 64;
                $uv = 32;
                break;

            case 65536:
                $maxX = $maxY = 16;

                $width = 128;
                $uv = 64;
        }

        $skin = substr($this->skinData, ($pos = ($width * $maxX * 4)) - 4, $pos);

        for ($y = 0; $y < $maxY; ++$y) {
            for ($x = 1; $x < $maxX + 1; ++$x) {
                if (!isset($strArray[$y])) {
                    $strArray[$y] = "";
                }
                // layer 1
                $key = (($width * $y) + $maxX + $x) * 4;

                // layer 2
                $key2 = (($width * $y) + $maxX + $x + $uv) * 4;
                $a = ord($skin[$key2 + 3]);

                $strArray[$y] .= $a >= 127 ? substr($skin, $key2 - 1, 3) : substr($skin, $key - 1, 3);
            }
        }

        $this->setResult(base64_encode(implode('', $strArray)));
    }

    public function onCompletion(Server $server) {
        $plugin = $server->getPluginManager()->getPlugin("MyPath");
        if(!$plugin) return;

        $plugin->sendPlayerFace($this->eid, $this->getResult());
    }


}
