<?php


namespace VirVolta\ArmorEffect;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\event\entity\EntityArmorChangeEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;

class ArmorEffect extends PluginBase implements Listener
{
    private $config;

    /**
     * @param mixed $config
     */
    public function setData(Config$config): void
    {
        $this->config = $config;
    }
    
    public function getData(): Config
    {
        return $this->config;
    }

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        @mkdir($this->getDataFolder());

        if (!file_exists($this->getDataFolder() . "config.yml")) {

            $this->saveResource('config.yml');
        }

        $this->setData(new Config($this->getDataFolder() . 'config.yml', Config::YAML));
    }

    public function onArmor(EntityArmorChangeEvent $event)
    {
        $player = $event->getEntity();

        if ($player instanceof Player) {

            $new = $event->getNewItem();
            $old = $event->getOldItem();

            $configs = $this->getData()->getAll();
            $ids = array_keys($configs);

            if (in_array($new->getId(), $ids)) {

                $array = $this->getData()->getAll()[$new->getId()];

                if ($array["message"] != null) {

                    $player->sendMessage($array["message"]);

                }

                $effects = $array["effect"];

                foreach ($effects as $effectid => $arrayeffect) {

                    $eff = new EffectInstance(
                        Effect::getEffect($effectid),
                        9999999 * 20,
                        (int)$arrayeffect["amplifier"],
                        (bool)$arrayeffect["visible"]
                    );

                    $player->addEffect($eff);

                }

            } else if (in_array($old->getId(), $ids)) {

                $array = $this->getData()->getAll()[$old->getId()];
                $effects = $array["effect"];

                foreach ($effects as $effectid => $arrayeffect) {

                    $player->removeEffect($effectid);

                }

            }

        }

    }

}