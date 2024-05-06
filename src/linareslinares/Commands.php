<?php

namespace linareslinares;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TE;

class Commands extends Command
{

    public function __construct(){
        parent::__construct("staff", "Staffmode", "/staff");
        $this->setPermission("versalstaff.cmd");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        $name = $sender->getName();
        if (!$this->testPermission($sender)){
            return true;
        }
        if (!$sender->hasPermission("versalstaff.cmd")){
            $sender->sendMessage(Loader::getInstance()->prefix. TE::RED . "You do not have permission to use this command");
            return true;
        }
        if ($sender instanceof Player){
            if(!Loader::getInstance()->isStaff($name)){
                if (!isset(Loader::getInstance()->InvPlayer[$name])) {
                    Loader::getInstance()->InvPlayer[$name] = $sender->getInventory()->getContents();

                    Loader::getInstance()->getItems($sender);
                    $sender->setAllowFlight(true);

                    $sender->sendMessage(Loader::getInstance()->prefix. "§aMode activado!");
                    $sender->sendMessage(Loader::getInstance()->prefix. "§eSe guardo tu inventario.");
                    $sender->sendTitle(Loader::getInstance()->prefix , "§cEl uso inadecuado, es Baneable! ");
                    Loader::getInstance()->setStaff($name);
                }
            }else{
                $sender->getInventory()->setContents(Loader::getInstance()->InvPlayer[$name]);
                $sender->sendMessage(Loader::getInstance()->prefix. "§e Mode desactivado");
                $sender->sendMessage(Loader::getInstance()->prefix. "§eSe regreso tu inventario.");
                $sender->getEffects()->clear();
                $sender->setAllowFlight(false);
                $sender->setGamemode(GameMode::SURVIVAL());
                Loader::getInstance()->quitStaff($name);
                unset(Loader::getInstance()->InVanish[$sender->getName()]);
                unset(Loader::getInstance()->InvPlayer[$name]);

            }
        }
        return true;
    }

}