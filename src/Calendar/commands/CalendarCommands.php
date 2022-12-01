<?php

namespace Calendar\commands;

use Calendar\Main;
use Calendar\PagedGui;
use Calendar\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class CalendarCommands extends Command
{

    public function __construct(){
        parent::__construct(Main::getInstance()->getConfig()->getNested("command.command-name"), Main::getInstance()->getConfig()->getNested("command.command-description"), "/calendar", Main::getInstance()->getConfig()->getNested("command.command-aliases"));
    }

    /**
     * @inheritDoc
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof Player) return;

        //Utils::calendarGUI($sender);
        $pagined = new PagedGui($sender);
        $pagined->initInv();
        $pagined->open();

        $pData = new Config(Main::getInstance()->getDataFolder() . "player-data.yml", Config::YAML);
        $exp = explode(":", Main::getInstance()->getConfig()->getNested("GUI.calendar-item"));
        foreach (Main::getInstance()->getConfig()->get("reward") as $day => $reward){
            $item = ItemFactory::getInstance()->get((int)$exp[0], (int)$exp[1]);
            $item->setCustomName("Day " . $day);
            $nbt = $item->getNamedTag();
            $nbt->setString("reward", $reward);
            $nbt->setString("day", $day);
            if(!isset($pData->get($sender->getName())[$day])){
                $nbt->setString("open", "false");
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::INFINITY()));
            }

            $pagined->addContent($item);
        }
        
    }
}
