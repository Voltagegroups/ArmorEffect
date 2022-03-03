<?php

namespace VirVolta\ArmorEffect;

use pocketmine\data\bedrock\EffectIdMap;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\Living;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\inventory\Inventory;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;

class ArmorEffect extends PluginBase implements Listener
{
    private const EFFECT_MAX_DURATION = 2147483647;
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
            if ($targetItem instanceof Armor) {
                $slot = $targetItem->getArmorSlot();
                $sourceItem = $player->getArmorInventory()->getItem($slot);

                $this->addEffects($player, $sourceItem, $targetItem);
            } else {
                if ($targetItem->getId() == ItemIds::AIR) {
                    $this->addEffects($player, ItemFactory::air(), $targetItem);
                }
            }
        }
        $player->getArmorInventory()->getListeners()->add(CallbackInventoryListener::onAnyChange(
            function(Inventory $inventory, int $slot, Item $oldItem) : void{
                if ($inventory instanceof ArmorInventory) {
                    $targetItem = $inventory->getItem($slot);
                    $this->addEffects($inventory->getHolder(), $oldItem, $targetItem);
                }
            }
        )

            /*new CallbackInventoryListener(
            function (Inventory $inventory, int $slot, Item $oldItem) : void {
                if ($inventory instanceof ArmorInventory) {
                    $targetItem = $inventory->getItem($slot);
                    $this->addEffects($inventory->getHolder(), $oldItem, $targetItem);
                }
            },
            function(Inventory $inventory, array $oldItems) : void {
                //NOTHING
            }
        )*/);
    }

    //look for all entity
    private function addEffects(Living $player, Item $sourceItem, Item $targetItem) : void {
        $configs = $this->getData()->getAll();
        $ids = array_keys($configs);

        if (in_array($sourceItem->getId(), $ids)) {
            $array = $this->getData()->getAll()[$sourceItem->getId()];
            $effects = $array["effect"];

            foreach ($effects as $effectid => $arrayeffect) {
                $player->getEffects()->remove(EffectIdMap::getInstance()->fromId($effectid));
            }
        }

        if (in_array($targetItem->getId(), $ids)) {
            $array = $this->getData()->getAll()[$targetItem->getId()];
            if ($array["message"] != null) {
                if ($player instanceof Player) {
                    $player->sendMessage($array["message"]);
                }
            }
            $effects = $array["effect"];

            foreach ($effects as $effectid => $arrayeffect) {
                $eff = new EffectInstance(
                    EffectIdMap::getInstance()->fromId($effectid),
                    self::EFFECT_MAX_DURATION,
                    (int)$arrayeffect["amplifier"],
                    (bool)$arrayeffect["visible"]
                );
                $player->getEffects()->add($eff);
            }
        }
    }
}