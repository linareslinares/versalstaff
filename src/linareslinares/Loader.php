<?php

namespace linareslinares;

use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use function array_search;
use function in_array;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TE;
use linareslinares\StaffUI;

class Loader extends PluginBase
{
    public $prefix = TE::DARK_GRAY."[".TE::RED."STAFF".TE::DARK_GRAY."] ";
    public $mt;
    public $lx;
    public $freeze;
    public $freeze_tag;
    public $staff = [];
    public $InVanish = [];
    public $InvPlayer = [];
    public $targetPlayer = [];
    public $Playercon = [];
    public $mutedPlayer = [];
    public $frozen = array();
    public Config $config;

    use SingletonTrait;
    public function onLoad(): void {
        self::setInstance($this);
    }
    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        $this->getServer()->getCommandMap()->register("staff", new Commands());
        $this->getServer()->getCommandMap()->register("report", new reportCommand());
        $this->saveDefaultConfig();
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->saveResource("config.yml");

        $this->lx = new \SQLite3($this->getDataFolder() . "BanPlayers.yml");
        $this->mt = new \SQLite3($this->getDataFolder() . "PlayersMute.yml");
        $this->lx->exec("CREATE TABLE IF NOT EXISTS banPlayers(player TEXT PRIMARY KEY, banTime INT, reason TEXT, staff TEXT);");
        $this->mt->exec("CREATE TABLE IF NOT EXISTS plaayersMute(player TEXT PRIMARY KEY, muteTime INT, reason TEXT, staff TEXT);");

        $this->freeze = "§7[§cFREEZE§7]§r ";
        $this->freeze_tag = "§7[§cFREEZE§7]§r ";
    }

    public function isStaff($name){
        return in_array($name, $this->staff);
    }
    public function setStaff($name){
        $this->staff[$name] = $name;
    }

    public function quitStaff($name){
        if(!$this->isStaff($name)){
            return;
        }
        unset($this->staff[$name]);
        unset($this->InVanish[$name]);
        unset($this->InvPlayer[$name]);
    }

    public function getItems(Player $player){
        $player->getInventory()->clearAll();

        $tp = VanillaItems::COMPASS();
        $tp->setCustomName(TE::LIGHT_PURPLE."USERS");
        $tp->setCount(1);

        $freez = VanillaBlocks::ICE()->asItem();
        $freez->setCustomName(TE::LIGHT_PURPLE."FREEZE");
        $freez->setCount(1);

        $vanish = VanillaItems::BLAZE_ROD();
        $vanish->setCustomName(TE::LIGHT_PURPLE."VANISH");
        $vanish->setCount(1);

        $salir = VanillaBlocks::BED()->setColor(DyeColor::BLUE())->asItem();
        $salir->setCustomName(TE::RED."EXIT");
        $salir->setCount(1);

        $ban = VanillaBlocks::ANVIL()->asItem();
        $ban->setCustomName(TE::LIGHT_PURPLE."BANNED");
        $ban->setCount(1);

        $mute = VanillaItems::NETHER_STAR();
        $mute->setCustomName(TE::LIGHT_PURPLE."MUTE");
        $mute->setCount(1);

        $info = VanillaBlocks::MOB_HEAD()->asItem();
        $info->setCustomName(TE::LIGHT_PURPLE."UNBAN/UNMUTE");
        $info->setCount(1);

        $player->getInventory()->setItem(0, $tp);
        $player->getInventory()->setItem(1, $freez);
        $player->getInventory()->setItem(2, $vanish);
        $player->getInventory()->setItem(4, $salir);
        $player->getInventory()->setItem(6, $ban);
        $player->getInventory()->setItem(7, $mute);
        $player->getInventory()->setItem(8, $info);
    }
}