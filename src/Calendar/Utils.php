<?php

namespace Calendar;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\inventory\Inventory;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;

class Utils
{

    public static function reward(string $reward, Player $player, $day = 01){
        $exp = explode(",", $reward);

        var_dump($exp);
        switch ($exp[0]){
            case "command":
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage()), str_replace(["{player}"], [$player->getName()], $exp[1]));
                $player->sendMessage(str_replace(["{day}", "{rewardName}"], [$day, $exp[2]], Main::getInstance()->getConfig()->getNested("text.message.claim-reward")));
                break;

            case "item":
                $drop = ItemFactory::getInstance()->get((int)$exp[1], (int)$exp[2], (int)$exp[3]);
                if(isset($exp[4]) && isset($exp[5])){
                    $drop->addEnchantment(new EnchantmentInstance(StringToEnchantmentParser::getInstance()->parse($exp[4]), $exp[5]));
                }

                $drop->setCustomName($exp[6]);

                $player->getInventory()->addItem($drop);
                $player->sendMessage(str_replace(["{day}", "{rewardName}"], [$day, $exp[7]], Main::getInstance()->getConfig()->getNested("text.message.claim-reward")));
                break;
        }
    }


}