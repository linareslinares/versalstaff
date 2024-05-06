<?php

namespace linareslinares;

use linareslinares\EventListener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TE;
use linareslinares\utils\DiscordWebhook\SendAsync;
class reportCommand extends Command
{
    public function __construct(){
        parent::__construct("report", "Report a player", "/report");
        $this->setPermission("versalstaff.report");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$this->testPermission($sender)){
            return true;
        }
        if(!$sender->hasPermission("versalstaff.report")){
            $sender->sendMessage(Loader::getInstance()->prefix. TE::RED . "No tienes permisos para usar esto.");
            return true;
        }
        if($sender instanceof Player){
            if(empty($args[0])){
                $sender->sendMessage(Loader::getInstance()->prefix.TE::DARK_GRAY . "Ingresa el nick y razon del reporte.");
                EventListener::PlaySound($sender, "mob.villager.no", 3, 1);
                return;
            }

            $report = $args[0];
            if($sender->getName() === $report){
                $sender->sendMessage(Loader::getInstance()->prefix. TE::DARK_GRAY. "No puedes hacer un reporte propio.");
                EventListener::PlaySound($sender, "mob.villager.no", 3, 1);
                return;
            }

            if(empty($args[1])){
                $sender->sendMessage(Loader::getInstance()->prefix.TE::DARK_GRAY . "Ingresa la razon del reporte.");
                EventListener::PlaySound($sender, "mob.villager.no", 3, 1);
                return;
            }

            unset($args[0]);
            $reason = implode(" ", $args);
            $sender->sendMessage(Loader::getInstance()->prefix. TE::GREEN."Tu reporte fue enviado al staff.");
            EventListener::PlaySound($sender, "random.levelup", 3, 1);
            $msgd = "======[**REPORTE**]======".TE::EOL."- By: ". $sender->getName().TE::EOL."- Nick: "."**".$report."**".TE::EOL."- Rason: "."**".$reason."**".TE::EOL."=======[|| @here ||]=======";
            $this->sendWebHook($msgd, $sender->getName());
            foreach (Loader::getInstance()->getServer()->getOnlinePlayers() as $onlinePlayer) {
                if ($onlinePlayer->hasPermission("versalstaff.cmd")) {
                    $msg = TE::DARK_GRAY."======[". TE::RED. "REPORTE". TE::DARK_GRAY. "]======". TE::EOL. TE::YELLOW. "By: ".TE::GREEN. $sender->getName(). TE::EOL. TE::YELLOW. "Nick: ". TE::RED. $report. TE::EOL. TE::YELLOW. "Razon: ".TE::RED. $reason. TE::EOL. TE::DARK_GRAY."======[". TE::RED. "REPORTE". TE::DARK_GRAY. "]======";
                    EventListener::PlaySound($onlinePlayer, "random.anvil_use", 3, 1);
                    $onlinePlayer->sendMessage($msg);
                }

            }
        }else{
            $sender->sendMessage(Loader::getInstance()->prefix. "Usa este comando en juego.");
        }
        return true;
    }

    public function sendWebHook(string $msg, string $player = "nolog"){
        $name = "STAFF ALERT";
        $webhook = Loader::getInstance()->config->get("webhook_url");
        $cleanMsg = $this->cleanMessage($msg);
        $curlopts = [
            "content" => $cleanMsg,
            "username" => $name
        ];

        if($cleanMsg === ""){
            Loader::getInstance()->getLogger()->warning(TE::RED."ERROR: No se pueden enviar mensajes vacios.");
            return;
        }

        Loader::getInstance()->getServer()->getAsyncPool()->submitTask(new utils\DiscordWebhook\SendAsync($player, $webhook, serialize($curlopts)));
    }

    public function cleanMessage(string $msg) : string{
        $banned = Loader::getInstance()->config->getNested("banned_list", []);
        return str_replace($banned,'',$msg);
    }

}