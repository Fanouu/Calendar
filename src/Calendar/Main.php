<?php

namespace Calendar;

use Calendar\commands\CalendarCommands;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

class Main extends PluginBase
{
    use SingletonTrait;

    protected function onEnable(): void{

        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }

        self::$instance = $this;

        $this->saveDefaultConfig();
        Server::getInstance()->getCommandMap()->register("calendar", new CalendarCommands());

        
    }

}