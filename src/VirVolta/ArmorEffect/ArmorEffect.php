<?php

namespace VirVolta\ArmorEffect;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\StringToEffectParser;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

/**
 * Class ArmorEffect
 * @package VirVolta\ArmorEffect
 */
class ArmorEffect extends PluginBase implements Listener
{
    /**
     * The maximum duration of an effect
     * @var int
     */
    private const EFFECT_MAX_DURATION = 2147483647;

    /**
     * The plugin config
     * @var Config
     */
    private static Config $config;

    /**
     * Get the plugin config
     * @return Config
     */
    public static function getData(): Config
    {
        return self::$config;
    }

    /**
     * Enable the plugin ArmorEffect Loader
     * @return void
     */
    public function onEnable(): void
    {
        @mkdir($this->getDataFolder());

        if (!file_exists($this->getDataFolder() . "config.yml")) {
            $this->saveResource('config.yml');
        }

        self::$config = new Config($this->getDataFolder() . 'config.yml', Config::YAML);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    /**
     * Add the effects to the player
     * @param Player $player
     * @param Item $sourceItem
     * @param Item $targetItem
     * @return void
     */
    private function addEffects(Player $player, Item $sourceItem, Item $targetItem): void
    {
        $configs = $this->getData()->getAll();
        $names = array_keys($configs);

        $nameOfSourceItem = StringToItemParser::getInstance()->lookupAliases($sourceItem);
        if (isset($nameOfSourceItem[0])) {
            $nameOfSourceItem = $nameOfSourceItem[0];
            if (in_array($nameOfSourceItem, $names)) {
                $array = $this->getData()->getAll()[$nameOfSourceItem];
                $effects = $array["effects"];

                foreach ($effects as $effectname => $arrayeffect) {
                    $effect = StringToEffectParser::getInstance()->parse($effectname);
                    $player->getEffects()->remove($effect);
                }
            }
        }

        $nameOfTargetItem = StringToItemParser::getInstance()->lookupAliases($targetItem);
        if (isset($nameOfTargetItem[0])) {
            $nameOfTargetItem = $nameOfTargetItem[0];
            if (in_array($nameOfTargetItem, $names)) {
                $array = $this->getData()->getAll()[$nameOfTargetItem];
                if ($array["message"] != null) {
                    $player->sendMessage($array["message"]);
                }
                $effects = $array["effects"];

                foreach ($effects as $effectname => $arrayeffect) {
                    $effect = StringToEffectParser::getInstance()->parse($effectname);
                    if (!is_null($effect)) {
                        $eff = new EffectInstance(
                            $effect,
                            self::EFFECT_MAX_DURATION,
                            (int)$arrayeffect["amplifier"],
                            (bool)$arrayeffect["visible"]
                        );
                        $player->getEffects()->add($eff);
                    }
                }
            }
        }
    }

    /**
     * Add the effects to the player when he joins
     * @param PlayerJoinEvent $event
     * @priority MONITOR
     * @return void
     */
    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        foreach ($player->getArmorInventory()->getContents() as $targetItem) {
            if ($targetItem instanceof Armor) { //if the item is not armor is not my problem
                $slot = $targetItem->getArmorSlot();
                $sourceItem = $player->getArmorInventory()->getItem($slot);

                $this->addEffects($player, $sourceItem, $targetItem);
            } else {
                $this->addEffects($player, VanillaItems::AIR(), $targetItem);
            }
        }
    }

    /**
     * Add the effects to the player when he wears the armor
     * @param PlayerItemUseEvent $event
     * @priority MONITOR
     * @return void
     */
    public function onUse(PlayerItemUseEvent $event): void
    {
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

    /**
     * Add the effects to the player when he wears the armor
     * @param InventoryTransactionEvent $event
     * @priority MONITOR
     * @return void
     */
    public function onArmor(InventoryTransactionEvent $event): void
    {
        $transaction = $event->getTransaction();
        $player = $transaction->getSource();

        foreach ($transaction->getActions() as $action) {
            if ($action instanceof SlotChangeAction) {
                if ($action->getInventory() instanceof ArmorInventory) {
                    $sourceItem = $action->getSourceItem();
                    $targetItem = $action->getTargetItem();

                    if (!$event->isCancelled()) {
                        $this->addEffects($player, $sourceItem, $targetItem);
                        return;
                    }
                }
            }
        }
    }
}
