<?php

namespace linareslinares;

use linareslinares\utils\FormAPI\SimpleForm;
use linareslinares\utils\FormAPI\CustomForm;
use linareslinares\utils\FormAPI\ModalForm;
use linareslinares\utils\FormAPI\Form;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat as TE;

class StaffUI
{
    use SingletonTrait;
    public function onLoad(): void {
        self::setInstance($this);
    }
    public function getInfoMenu($player){
        $form = new SimpleForm(function (Player $player, ?int $data = null){
            if($data === null){
                return true;
            }

            switch ($data){
                case 0:
                    $this->getCheckMuteUI($player);
                    EventListener::PlaySound($player, "random.levelup", 5, 1);
                    break;
                case 1:
                    $this->getCheckBanUI($player);
                    EventListener::PlaySound($player, "random.levelup", 5, 1);
                    break;

            }
        });
        $form->setTitle(TE::RED. TE::BOLD."UNBAN & UNMUTE");
        $form->setContent(TE::YELLOW."Selecciona la lista de jugadores silenciados o suspendidos, para mas informacion.", 0, );
        $form->addButton(TE::RED. TE::BOLD."SILENCIADOS", 0, "textures/ui/mute_on");
        $form->addButton(TE::RED. TE::BOLD."SUSPENDIDOS", 0, "textures/ui/slowness_effect");
        $form->sendToPlayer($player);
        return $form;
    }

    public function getTeleportUI(Player $pl){
        $form = new SimpleForm(function (Player $pl, $data = null) {
            $target = $data;
            if ($target === null) {
                return true;
            }
            Loader::getInstance()->targetPlayer[$pl->getName()] = $target;
            EventListener::getInstance()->getTp($pl);
        });
        $form->setTitle(TE::YELLOW. TE::BOLD. "LISTA DE USUARIOS");
        $form->setContent(TE::YELLOW."Selecciona el usuario al que deseas teletransportarte.");
        foreach (Server::getInstance()->getOnlinePlayers() as $on) {
            $form->addButton(TE::RED.$on->getName(), -1, "", $on->getName());
        }
        $form->sendToPlayer($pl);
        return $form;
    }

    public function getMuteList(Player $pl){
        $form = new SimpleForm(function (Player $pl, $data = null) {
            $target = $data;
            if ($target === null) {
                return true;
            }
            Loader::getInstance()->mutedPlayer[$pl->getName()] = $target;
            $this->getMuteUI($pl);
        });

        $form->setTitle(TE::YELLOW. TE::BOLD. "SILENCIAR USUARIOS");
        $form->setContent(TE::YELLOW."Selecciona un usuario para continuar.");

        foreach (Server::getInstance()->getOnlinePlayers() as $on) {

            $form->addButton(TE::RED.$on->getName(), -1, "", $on->getName());

        }

        $form->sendToPlayer($pl);

        return $form;

    }

    public function getMuteUI($player){
        $form = new CustomForm(function (Player $player, array $data = null){
            if($data === null){
                return true;
            }
            $result = $data[0];
            if(isset(Loader::getInstance()->mutedPlayer[$player->getName()])){
                if(Loader::getInstance()->mutedPlayer[$player->getName()] == $player->getName()){
                    $player->sendMessage(Loader::getInstance()->config->get("MuteMe"));
                    return true;
                }
                $now = time();
                $day = ($data[1] * 86400);
                $hour = ($data[2] * 3600);
                if($data[3] > 1){
                    $min = ($data[3] * 60);
                } else {
                    $min = 60;
                }
                $banTime = $now + $day + $hour + $min;
                $banInfo = Loader::getInstance()->mt->prepare("INSERT OR REPLACE INTO plaayersMute (player, muteTime, reason, staff) VALUES (:player, :muteTime, :reason, :staff);");
                $banInfo->bindValue(":player", Loader::getInstance()->mutedPlayer[$player->getName()]);
                $banInfo->bindValue(":muteTime", $banTime);
                $banInfo->bindValue(":reason", $data[4]);
                $banInfo->bindValue(":staff", $player->getName());
                $banInfo->execute();
                $target = Loader::getInstance()->getServer()->getPlayerExact(Loader::getInstance()->mutedPlayer[$player->getName()]);
                if($target instanceof Player){
                    $target->sendMessage(Loader::getInstance()->prefix. str_replace(["{day}", "{hour}", "{minute}", "{reason}", "{staff}"], [$data[1], $data[2], $data[3], $data[4], $player->getName()], Loader::getInstance()->config->get("MuteChat")));
                }
                Loader::getInstance()->getServer()->broadcastMessage(Loader::getInstance()->prefix. str_replace(["{player}", "{day}", "{hour}", "{minute}", "{reason}", "{staff}"], [Loader::getInstance()->mutedPlayer[$player->getName()], $data[1], $data[2], $data[3], $data[4], $player->getName()], Loader::getInstance()->config->get("MuteBroadcast")));
                unset(Loader::getInstance()->mutedPlayer[$player->getName()]);

            }
        });
        $list[] = Loader::getInstance()->mutedPlayer[$player->getName()];
        $form->setTitle(TE::RED. TE::BOLD. "SILENCIAR");
        $form->addDropdown("\nUsuario seleccionado", $list);
        $form->addSlider("Dias", 0, 30, 1);
        $form->addSlider("Horas", 0, 24, 1);
        $form->addSlider("Minutos", 0, 60, 5);
        $form->addInput("Razón");
        $form->sendToPlayer($player);
        return $form;
    }

    public function getCheckMuteUI($player){
        $form = new SimpleForm(function (Player $player, $data = null){
            if($data === null){
                return true;
            }
            Loader::getInstance()->mutedPlayer[$player->getName()] = $data;
            $this->getMuteInfoUI($player);
        });
        $banInfo = Loader::getInstance()->mt->query("SELECT * FROM plaayersMute;");
        $array = $banInfo->fetchArray(SQLITE3_ASSOC);
        if (empty($array)) {
            $player->sendMessage(Loader::getInstance()->prefix. Loader::getInstance()->config->get("NoMutePlayers"));
            return true;
        }
        $form->setTitle(TE::RED.TE::BOLD. "SILENCIADOS");
        $form->setContent(TE::YELLOW."Lista de jugadores silenciados.");
        $banInfo = Loader::getInstance()->mt->query("SELECT * FROM plaayersMute;");
        $i = -1;

        $players = [];

        while ($resultArr = $banInfo->fetchArray(SQLITE3_ASSOC)) {
            $j = $i + 1;
            $banPlayer = $resultArr['player'];
            $players[] = $banPlayer;
            $i = $i + 1;
        }

        sort($players);

        foreach ($players as $pp){
            $form->addButton(TE::BOLD . "$pp", -1, "", $pp);
        }

        $form->sendToPlayer($player);
        return $form;
    }

    public function getMuteInfoUI($player){
        $form = new SimpleForm(function (Player $player, int $data = null){
            $result = $data;
            if($result === null){
                return true;
            }
            switch($result){
                case 0:
                    $banplayer = Loader::getInstance()->mutedPlayer[$player->getName()];
                    $banInfo = Loader::getInstance()->mt->query("SELECT * FROM plaayersMute WHERE player = '$banplayer';");
                    $array = $banInfo->fetchArray(SQLITE3_ASSOC);
                    if (!empty($array)) {
                        Loader::getInstance()->mt->query("DELETE FROM plaayersMute WHERE player = '$banplayer';");
                        $player->sendMessage(Loader::getInstance()->prefix. str_replace(["{player}"], [$banplayer], Loader::getInstance()->config->get("UnMute")));
                    }
                    unset(Loader::getInstance()->mutedPlayer[$player->getName()]);
                    break;
            }
        });
        $banPlayer = Loader::getInstance()->mutedPlayer[$player->getName()];
        $banInfo = Loader::getInstance()->mt->query("SELECT * FROM plaayersMute WHERE player = '$banPlayer';");
        $array = $banInfo->fetchArray(SQLITE3_ASSOC);
        $text = TE::RED . " Error " . $banPlayer . " información!";
        if (!empty($array)) {
            $banTime = $array['muteTime'];
            $reason = $array['reason'];
            $staff = $array['staff'];
            $now = time();
            if($banTime < $now){
                $banplayer = Loader::getInstance()->mutedPlayer[$player->getName()];
                $banInfo = Loader::getInstance()->mt->query("SELECT * FROM plaayersMute WHERE player = '$banplayer';");
                $array = $banInfo->fetchArray(SQLITE3_ASSOC);
                if (!empty($array)) {
                    Loader::getInstance()->mt->query("DELETE FROM plaayersMute WHERE player = '$banplayer';");
                    $player->sendMessage(Loader::getInstance()->prefix. Loader::getInstance()->config->get("AutoUnMutePlayer"));
                }
                unset(Loader::getInstance()->mutedPlayer[$player->getName()]);
                return true;
            }
            $remainingTime = $banTime - $now;
            $day = floor($remainingTime / 86400);
            $hourSeconds = $remainingTime % 86400;
            $hour = floor($hourSeconds / 3600);
            $minuteSec = $hourSeconds % 3600;
            $minute = floor($minuteSec / 60);
            $remainingSec = $minuteSec % 60;
            $second = ceil($remainingSec);

            $text = str_replace(["{day}", "{hour}", "{minute}", "{second}", "{reason}", "{staff}"], [$day, $hour, $minute, $second, $reason, $staff], Loader::getInstance()->config->get("MuteInfoUI"));
        }
        $form->setTitle(TE::RED . $banPlayer);

        $form->setContent($text);
        $form->addButton("§l§cREMOVER SILENCIO");
        $form->sendToPlayer($player);
        return $form;
    }

    public function getBanList(Player $pl){
        $form = new SimpleForm(function (Player $pl, $data = null) {
            $target = $data;
            if ($target === null) {
                return true;
            }
            Loader::getInstance()->targetPlayer[$pl->getName()] = $target;
            $this->getBanUI($pl);
        });

        $form->setTitle(TE::RED. TE::BOLD. "SUSPENDER USUARIOS");
        $form->setContent(TE::YELLOW. "§eSelecciona un usuario para continuar.");

        foreach (Server::getInstance()->getOnlinePlayers() as $on) {

            $form->addButton(TE::RED.$on->getName(), -1, "", $on->getName());

        }

        $form->sendToPlayer($pl);
        return $form;

    }

    public function getBanUI($player){
        $form = new CustomForm(function (Player $player, array $data = null){
            if($data === null){
                return true;
            }
            $result = $data[0];
            if(isset(Loader::getInstance()->targetPlayer[$player->getName()])){
                if(Loader::getInstance()->targetPlayer[$player->getName()] == $player->getName()){
                    $player->sendMessage(Loader::getInstance()->prefix. Loader::getInstance()->config->get("BanMe"));
                    return true;
                }
                $now = time();
                $day = ($data[1] * 86400);
                $hour = ($data[2] * 3600);
                if($data[3] > 1){
                    $min = ($data[3] * 60);
                } else {
                    $min = 60;
                }
                $banTime = $now + $day + $hour + $min;
                $banInfo = Loader::getInstance()->lx->prepare("INSERT OR REPLACE INTO banPlayers (player, banTime, reason, staff) VALUES (:player, :banTime, :reason, :staff);");
                $banInfo->bindValue(":player", Loader::getInstance()->targetPlayer[$player->getName()]);
                $banInfo->bindValue(":banTime", $banTime);
                $banInfo->bindValue(":reason", $data[4]);
                $banInfo->bindValue(":staff", $player->getName());
                $banInfo->execute();
                $target = Loader::getInstance()->getServer()->getPlayerExact(Loader::getInstance()->targetPlayer[$player->getName()]);
                if($target instanceof Player){
                    $target->kick(str_replace(["{day}", "{hour}", "{minute}", "{reason}", "{staff}"], [$data[1], $data[2], $data[3], $data[4], $player->getName()], Loader::getInstance()->config->get("BanTempMessage")));
                }
                Loader::getInstance()->getServer()->broadcastMessage(Loader::getInstance()->prefix. str_replace(["{player}", "{day}", "{hour}", "{minute}", "{reason}", "{staff}"], [Loader::getInstance()->targetPlayer[$player->getName()], $data[1], $data[2], $data[3], $data[4], $player->getName()], Loader::getInstance()->config->get("BanTempBroadcast")));
                unset(Loader::getInstance()->targetPlayer[$player->getName()]);

            }
        });
        $list[] = Loader::getInstance()->targetPlayer[$player->getName()];
        $form->setTitle(TE::RED. TE::BOLD."§cSUSPENDER USUARIO");
        $form->addDropdown("\nUsuario seleccionado", $list);
        $form->addSlider("Dias", 0, 30, 1);
        $form->addSlider("Horas", 0, 24, 1);
        $form->addSlider("Minutos", 0, 60, 5);
        $form->addInput("Razón");
        $form->sendToPlayer($player);
        return $form;
    }

    public function getCheckBanUI($player){
        $form = new SimpleForm(function (Player $player, $data = null){
            if($data === null){
                return true;
            }
            Loader::getInstance()->targetPlayer[$player->getName()] = $data;
            $this->getBanInfoUI($player);
        });
        $banInfo = Loader::getInstance()->lx->query("SELECT * FROM banPlayers;");
        $array = $banInfo->fetchArray(SQLITE3_ASSOC);
        if (empty($array)) {
            $player->sendMessage(Loader::getInstance()->prefix. Loader::getInstance()->config->get("NoBanPlayers"));
            return true;
        }
        $form->setTitle(TE::RED. TE::BOLD. "LISTA SUSPENDIDOS");
        $form->setContent(TE::YELLOW."Lista de usuarios suspendidos.");
        $banInfo = Loader::getInstance()->lx->query("SELECT * FROM banPlayers;");
        $i = -1;

        $players = [];

        while ($resultArr = $banInfo->fetchArray(SQLITE3_ASSOC)) {
            $j = $i + 1;
            $banPlayer = $resultArr['player'];
            $players[] = $banPlayer;
            $i = $i + 1;
        }

        sort($players);

        foreach ($players as $pp){
            $form->addButton(TE::BOLD . "$pp", -1, "", $pp);
        }
        $form->sendToPlayer($player);
        return $form;
    }

    public function getBanInfoUI($player){
        $form = new SimpleForm(function (Player $player, int $data = null){
            $result = $data;
            if($result === null){
                return true;
            }
            switch($result){
                case 0:
                    $banplayer = Loader::getInstance()->targetPlayer[$player->getName()];
                    $banInfo = Loader::getInstance()->lx->query("SELECT * FROM banPlayers WHERE player = '$banplayer';");
                    $array = $banInfo->fetchArray(SQLITE3_ASSOC);
                    if (!empty($array)) {
                        Loader::getInstance()->lx->query("DELETE FROM banPlayers WHERE player = '$banplayer';");
                        $player->sendMessage(Loader::getInstance()->prefix. str_replace(["{player}"], [$banplayer], Loader::getInstance()->config->get("UnBan")));
                    }
                    unset(Loader::getInstance()->targetPlayer[$player->getName()]);
                    break;
            }
        });
        $banPlayer = Loader::getInstance()->targetPlayer[$player->getName()];
        $banInfo = Loader::getInstance()->lx->query("SELECT * FROM banPlayers WHERE player = '$banPlayer';");
        $array = $banInfo->fetchArray(SQLITE3_ASSOC);
        $text = TE::RED . " Error " . $banPlayer . " información!";
        if (!empty($array)) {
            $banTime = $array['banTime'];
            $reason = $array['reason'];
            $staff = $array['staff'];
            $now = time();
            if($banTime < $now){
                $banplayer = Loader::getInstance()->targetPlayer[$player->getName()];
                $banInfo = Loader::getInstance()->lx->query("SELECT * FROM banPlayers WHERE player = '$banplayer';");
                $array = $banInfo->fetchArray(SQLITE3_ASSOC);
                if (!empty($array)) {
                    Loader::getInstance()->lx->query("DELETE FROM banPlayers WHERE player = '$banplayer';");
                    $player->sendMessage(Loader::getInstance()->prefix. Loader::getInstance()->config["AutoUnBanPlayer"]);
                }
                unset(Loader::getInstance()->targetPlayer[$player->getName()]);
                return true;
            }
            $remainingTime = $banTime - $now;
            $day = floor($remainingTime / 86400);
            $hourSeconds = $remainingTime % 86400;
            $hour = floor($hourSeconds / 3600);
            $minuteSec = $hourSeconds % 3600;
            $minute = floor($minuteSec / 60);
            $remainingSec = $minuteSec % 60;
            $second = ceil($remainingSec);

            $text = str_replace(["{day}", "{hour}", "{minute}", "{second}", "{reason}", "{staff}"], [$day, $hour, $minute, $second, $reason, $staff], Loader::getInstance()->config->get("BanInfoUI"));
        }
        $form->setTitle(TE::RED . $banPlayer);
        $form->setContent($text);
        $form->addButton("§cREMOVER SUSPENCION");
        $form->sendToPlayer($player);
        return $form;
    }

    public function getFreezeList(Player $pl){
        $form = new SimpleForm(function (Player $pl, $data = null) {
            $target = $data;
            if ($target === null) {
                return true;
            }
            Loader::getInstance()->Playercon[$pl->getName()] = $target;
            $targe = Loader::getInstance()->getServer()->getPlayerExact(Loader::getInstance()->Playercon [$pl->getName()]);
            if(Loader::getInstance()->Playercon[$pl->getName()] == $pl->getName()){
                $pl->sendMessage(Loader::getInstance()->prefix. Loader::getInstance()->config->get("freezeMe"));
                return true;
            }
            if ($targe instanceof Player) {
                if(in_array($targe->getName(), Loader::getInstance()->frozen)) {
                    array_splice(Loader::getInstance()->frozen, array_search($targe->getName(), Loader::getInstance()->frozen), 1);
                    $targe->sendMessage(Loader::getInstance()->prefix. Loader::getInstance()->freeze ."§bYa no estas congelado.");
                    $targe->sendTitle("§l§bYa no estas", "§7Congelado!");
                    $targe->setNameTag(str_replace(Loader::getInstance()->freeze_tag, "", $targe->getNameTag()));
                    Loader::getInstance()->getServer()->broadcastMessage(Loader::getInstance()->prefix. Loader::getInstance()->freeze . "§e" . $targe->getName() . "§e dejó de estar congelado.");
                    $targe->sendMessage(Loader::getInstance()->prefix. Loader::getInstance()->freeze ."§eEste jugador ya estába congelado");

                } else {
                    array_push(Loader::getInstance()->frozen, $targe->getName());
                    Loader::getInstance()->getServer()->broadcastMessage(Loader::getInstance()->prefix. Loader::getInstance()->freeze ."§e" . $targe->getName() . "§e ha sido congelado.");
                    $targe->setNameTag(Loader::getInstance()->freeze_tag.$targe->getNametag());
                    $targe->sendActionBarMessage("§l§eCONGELADO");
                    $targe->sendTitle("§l§cFREEZE");
                    $targe->sendMessage(Loader::getInstance()->prefix. Loader::getInstance()->freeze . "§aHas sido congelado, habla con el staff");

                }
            }
            unset(Loader::getInstance()->Playercon[$pl->getName()]);
        });

        $form->setTitle(TE::BOLD. "§cCONGELAR USUARIO");
        $form->setContent("§eSelecciona un usuario para congelar\descongelar");
        foreach (Server::getInstance()->getOnlinePlayers() as $on) {
            $form->addButton(TE::RED.$on->getName(), -1, "", $on->getName());
        }
        $form->sendToPlayer($pl);
        return $form;

    }

}