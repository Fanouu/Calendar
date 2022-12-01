<?php

namespace Calendar;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\Thin;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\VanillaItems;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class PagedGui
{

    public int $page = 0;
    public int $max_page = 0;
    public array $button = [ "backButton" => 48, "nextButton" => 50, "item" => null];
    public array $contentSlot = [10, 11, 12, 13, 14, 15, 16, 19, 20, 21, 22, 23, 24, 25, 28, 29, 30, 31, 32, 33, 34, 37, 38, 39, 40, 41, 42, 43];
    public ?player $player = null;
    public ?InvMenu $inv = null;
    public array $content = [];

    public function __construct(Player $player, Item $button = null){
        if(is_null($button)){
            $this->button["item"] = VanillaItems::FEATHER();
        }

        $this->player = $player;
    }

    public function open(){
        $this->inv->send($this->player);
    }

    public function transaction(){
        $inv = $this->inv;
        $player = $this->player;
        $pagedui = $this;
        $inv->setListener(function (InvMenuTransaction $transaction) use ($player, $inv, $pagedui): InvMenuTransactionResult {
            $item = $transaction->getItemClicked();
            $pData = new Config(Main::getInstance()->getDataFolder() . "player-data.yml", Config::YAML);

            if($item->getCustomName() === Main::getInstance()->getConfig()->getNested("text.gui.restricted")){
                return $transaction->discard();
            }

            if($item->getCustomName() === Main::getInstance()->getConfig()->getNested("text.gui.next-item")){
                if($pagedui->max_page >= 1 && $pagedui->page < $pagedui->max_page){
                    $pagedui->page += 1;
                    $pagedui->setCurrentContent($pagedui->page);
                    $inv->setName(Main::getInstance()->getConfig()->getNested("GUI.name"));
                }
                $name = str_replace(["{page}", "{maxPage}"], [$this->page+1, $this->max_page+1], Main::getInstance()->getConfig()->getNested("GUI.name"));
                $this->inv->setName($name);
                return $transaction->discard();
            }

            if($item->getCustomName() === Main::getInstance()->getConfig()->getNested("text.gui.back-item")){
                if($pagedui->page > 0){
                    $pagedui->page -= 1;
                    $pagedui->setCurrentContent($pagedui->page);
                }
                $name = str_replace(["{page}", "{maxPage}"], [$this->page+1, $this->max_page+1], Main::getInstance()->getConfig()->getNested("GUI.name"));
                $this->inv->setName($name);
                return $transaction->discard();
            }

            $exp = explode(":", Main::getInstance()->getConfig()->getNested("GUI.calendar-item"));

            if($item->getId() === (int)$exp[0] && $item->getMeta() === (int)$exp[1]){
                $nbt = $item->getNamedTag();
                $itemDay = $nbt->getString("day", "01");
                $currentDay = date('d');

                if((int)$itemDay <= (int)$currentDay){
                    if(!isset($pData->get($player->getName())[$itemDay])){
                        $set = $pData->get($player->getName());
                        $set[$itemDay] = "true";
                        $pData->set($player->getName(), $set);
                        $pData->save();

                        Utils::reward($nbt->getString("reward"), $player, $itemDay);
                        $inv->onClose($player);
                    }
                }
            }

            return $transaction->discard();
        });
    }

    public function initInv(){
        $this->inv = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $name = str_replace(["{page}", "{maxPage}"], [$this->page+1, $this->max_page+1], Main::getInstance()->getConfig()->getNested("GUI.name"));
        $this->inv->setName($name);

        $exp = explode(":", Main::getInstance()->getConfig()->getNested("GUI.restricted-slot-item"));
        $restrictedItem = ItemFactory::getInstance()->get((int)$exp[0], (int)$exp[1])->setCustomName(Main::getInstance()->getConfig()->getNested("text.gui.restricted-slot"));
        $this->inv->getInventory()->setItem($this->button["backButton"], $this->button["item"]->setCustomName(Main::getInstance()->getConfig()->getNested("text.gui.back-item")));
        $this->inv->getInventory()->setItem($this->button["nextButton"], $this->button["item"]->setCustomName(Main::getInstance()->getConfig()->getNested("text.gui.next-item")));

        $this->transaction();
    }

    public function setCurrentContent(int $page = 0){
        foreach ($this->contentSlot as $slot){
            $this->inv->getInventory()->setItem($slot, VanillaItems::AIR());
        }
        foreach ($this->content[$page] as $index => $item){
            $this->setContent($item, $index, $page);
        }
    }

    public function removeContent($slot, $page = 0){
        $inv = $this->inv->getInventory();
        if($page === $this->page){
            $inv->removeItem($this->contentSlot[$slot]);
        }

        unset($this->content[$page][$slot]);
    }

    public function addContent(Item $item, $page = 0){
        if(!$this->canAddInPage($page)){
            $page += 1;
            $this->addContent($item, $page);
            return;
        }

        if(count($this->content) > $this->max_page+1){
            $this->max_page += 1;
        }

        $count = isset($this->content[$page]) ? count($this->content[$page]) : 0;
        $this->setContent($item, $count, $page);
    }

    public function setContent(Item $item, $slot = 0, $page = 0){
        $inv = $this->inv->getInventory();
        if($page === $this->page){
            $inv->setItem($this->contentSlot[$slot], $item);
        }
        $this->content[$page][$slot] = $item;
    }

    public function canAddInPage($page = 0){
        if(isset($this->content[$page]) && count($this->content[$page]) === count($this->contentSlot)){
            return false;
        }else return true;
    }

}