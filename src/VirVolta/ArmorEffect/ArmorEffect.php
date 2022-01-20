<?php

namespace VirVolta\ArmorEffect;

use pocketmine\data\bedrock\EffectIdMap;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;

class ArmorEffect extends PluginBase implements Listener
{
    private static Config $config;

    public static function getData() : Config{
        return self::$config;
    }

    public function onEnable() : void
    {
        @mkdir($this->getDataFolder());
        $this->getLogger()->notice("Loading the Armor Effect plugin");

        if (!file_exists($this->getDataFolder() . "config.yml")) {
            $this->saveResource('config.yml');
        }

        self::$config = new Config($this->getDataFolder() . 'config.yml', Config::YAML);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onJoin(PlayerJoinEvent $event) : void {
        $player = $event->getPlayer();
        foreach ($player->getArmorInventory()->getContents() as $targetItem) {
            if ($targetItem instanceof Armor) { //if the item is not armor is not my problem
                $slot = $targetItem->getArmorSlot();
                $sourceItem = $player->getArmorInventory()->getItem($slot);

                $this->addEffects($player, $sourceItem, $targetItem);
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event) : void {
        $player = $event->getPlayer();
        $targetItem = $event->getItem();

        if ($targetItem instanceof Armor) {
            $slot = $targetItem->getArmorSlot();
            $sourceItem = $player->getArmorInventory()->getItem($slot);

            if (!$event->isCancelled()) {
                $this->addEffects($player, $sourceItem, $targetItem);
            }
        }
    }

    public function onUse(PlayerItemUseEvent $event) : void {
        $player = $event->getPlayer();
        $targetItem = $event->getItem();

        if ($targetItem instanceof Armor) {
            $slot = $targetItem->getArmorSlot();
            $sourceItem = $player->getArmorInventory()->getItem($slot);

            if (!$event->isCancelled()) {
                $this->addEffects($player, $sourceItem, $targetItem);
            }
        }
    }

    public function onArmor(InventoryTransactionEvent $event) : void {
        $transaction = $event->getTransaction();
        $player = $transaction->getSource();

        foreach ($transaction->getActions() as $action) {
            if ($action instanceof SlotChangeAction) {
                if ($action->getInventory() instanceof ArmorInventory) {
                    $sourceItem = $action->getSourceItem();
                    $targetItem = $action->getTargetItem();

                    if ($player instanceof Player) {
                        if (!$event->isCancelled()) {
                            $this->addEffects($player, $sourceItem, $targetItem);
                            return;
                        }
                    }
                }
            }
        }
    }

    private function addEffects(Player $player, Item $sourceItem, Item $targetItem) : void {
        $configs = $this->getData()->getAll();
        $ids = array_keys($configs);

        if (in_array($targetItem->getId(), $ids)) {
            $array = $this->getData()->getAll()[$targetItem->getId()];
            if ($array["message"] != null) {
                $player->sendMessage($array["message"]);
            }
            $effects = $array["effect"];

            foreach ($effects as $effectid => $arrayeffect) {
                $eff = new EffectInstance(
                    EffectIdMap::getInstance()->fromId($effectid),
                    9999999 * 20,
                    (int)$arrayeffect["amplifier"],
                    (bool)$arrayeffect["visible"]
                );
                $player->getEffects()->add($eff);
            }

        } else if (in_array($sourceItem->getId(), $ids)) {
            $array = $this->getData()->getAll()[$sourceItem->getId()];
            $effects = $array["effect"];

            foreach ($effects as $effectid => $arrayeffect) {
                $player->getEffects()->remove(EffectIdMap::getInstance()->fromId($effectid));
            }
        }
    }

}