<?php

namespace linareslinares;

use linareslinares\StaffUI;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat as TE;

class EventListener implements Listener
{

    use SingletonTrait;
    public function onLoad(): void {
        self::setInstance($this);
    }
    public function onJoin(PlayerJoinEvent $event): void
    {
     $player = $event->getPlayer();
     if ($player->hasPermission("versalstaff.cmd")){
         $player->sendMessage(Loader::getInstance()->prefix. TE::GRAY. "Bienvenido de nuevo ". $player->getName(). ".");
     }
    }

    public function onQuitStaff(PlayerQuitEvent $ev){
        $pl = $ev->getPlayer();$name = $pl->getName();
        if (in_array ($pl->getName(), Loader::getInstance()->staff)) {
            $pl->getInventory()->clearAll();Loader::getInstance()->quitStaff($name);
        }
    }
    public function onPlaceStaff(BlockPlaceEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();
        $item = $player->getInventory()->getItemInHand();
        if($item->getName() === TE::RED."EXIT"){
            $event->cancel();
        }

        if (in_array ($player->getName(), Loader::getInstance()->staff)) {
            $event->cancel();

        }
    }
    public function onBreakStaff(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();
        if (in_array ($player->getName(), Loader::getInstance()->staff)) {
            $event->cancel();
        }
    }

    public function onExhaustStaff(PlayerExhaustEvent $ev){
        $pl = $ev->getPlayer();
        $name = $pl->getName();

        if (in_array ($pl->getName(), Loader::getInstance()->staff)) {
            $ev->cancel();
        }
    }

    public function NoTirar(PlayerDropItemEvent $event) {
        $pl = $event->getPlayer();
        $name = $pl->getName();

        if (in_array ($pl->getName(), Loader::getInstance()->staff)) {
            $event->cancel();
        }
    }

    public function NoStaffDamage(EntityDamageEvent $e) {
        $p = $e->getEntity();
        if($p instanceof Player) {
            if (in_array ($p->getName(), Loader::getInstance()->staff)) {
                $e->cancel();
            }
        }
    }

    public function getPlayerPlatform(Player $player): string
    {
        $extraData = $player->getPlayerInfo()->getExtraData();

        if ($extraData["DeviceOS"] === DeviceOS::ANDROID && $extraData["DeviceModel"] === "") {
            return "Linux";
        }

        return match ($extraData["DeviceOS"])
        {
            DeviceOS::ANDROID => "Android",
            DeviceOS::IOS => "iOS",
            DeviceOS::OSX => "macOS",
            DeviceOS::AMAZON => "FireOS",
            DeviceOS::GEAR_VR => "Gear VR",
            DeviceOS::HOLOLENS => "Hololens",
            DeviceOS::WINDOWS_10 => "Wind10",
            DeviceOS::WIN32 => "Windows 7 (Edu)",
            DeviceOS::DEDICATED => "Dedicated",
            DeviceOS::TVOS => "TV OS",
            DeviceOS::PLAYSTATION => "PlayStation",
            DeviceOS::NINTENDO => "Nintendo Switch",
            DeviceOS::XBOX => "Xbox",
            DeviceOS::WINDOWS_PHONE => "Windows Phone",
            default => "Unknown"
        };
    }

    public function onInteract(PlayerInteractEvent $ev) {
        $player = $ev->getPlayer();
        $item = $player->getInventory()->getItemInHand();

        if (!$ev->getAction() == $ev::RIGHT_CLICK_BLOCK and !$ev->getAction() == $ev::LEFT_CLICK_BLOCK) {
            return;
        }
        if($item->getName() == TE::LIGHT_PURPLE."USERS"){
            if(!$player->hasPermission("versalstaff.cmd")){
                $player->sendMessage(Loader::getInstance()->prefix. TE::RED."No eres staff.. Oh no tienes permisos");
            }else{
                StaffUI::getInstance()->getTeleportUI($player);
            }
        }
        if($item->getName() == TE::LIGHT_PURPLE."BANNED"){
            if(!$player->hasPermission("versalstaff.cmd")){
                $player->sendMessage(Loader::getInstance()->prefix. TE::RED."No eres staff.. Oh no tienes permisos");
            }else{
                StaffUI::getInstance()->getBanList($player);
            }
        }
        if($item->getName() == TE::LIGHT_PURPLE."MUTE"){
            if(!$player->hasPermission("versalstaff.cmd")){
                $player->sendMessage(Loader::getInstance()->prefix. TE::RED."No eres staff.. Oh no tienes permisos");
            }else{
                StaffUI::getInstance()->getMuteList($player);
            }
        }
        if($item->getName() == TE::LIGHT_PURPLE."FREEZE"){
            if(!$player->hasPermission("versalstaff.cmd")){
                $player->sendMessage(Loader::getInstance()->prefix. TE::RED."No eres staff.. Oh no tienes permisos");
            }else{
                StaffUI::getInstance()->getFreezeList($player);
            }
        }
        if($item->getName() == TE::LIGHT_PURPLE."VANISH"){
            if(!$player->hasPermission("versalstaff.cmd")){
                $player->sendMessage(Loader::getInstance()->prefix. TE::RED."No eres staff.. Oh no tienes permisos");
            }else{
                $this->VanishOn($player, true);
            }
        }
        if($item->getName() == TE::LIGHT_PURPLE."UNBAN/UNMUTE"){
            if(!$player->hasPermission("versalstaff.cmd")){
                $player->sendMessage(Loader::getInstance()->prefix. TE::RED."No eres staff.. Oh no tienes permisos");
            }else{
                $ev->cancel();
                StaffUI::getInstance()->getInfoMenu($player);
            }
        }
        if($item->getName() == TE::RED."EXIT"){
            if ($ev->getAction() == $ev::LEFT_CLICK_BLOCK) {
                Loader::getInstance()->getServer()->dispatchCommand($player, "staff");
                foreach(Server::getInstance()->getOnlinePlayers() as $players){
                    $players->showPlayer($player);
                }
            }else{
                $player->sendTip(TE::YELLOW. "LEFT CLICK TO EXIT");
            }
        }
    }

    public function VanishOn(Player $player): void {
        if(!isset(Loader::getInstance()->InVanish[$player->getName()])){
            Loader::getInstance()->InVanish[$player->getName()] = true;
            foreach(Server::getInstance()->getOnlinePlayers() as $players){
                $players->hidePlayer($player);
            }
            $player->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), 99999, 1, true));
            $this->PlaySound($player, "random.levelup", 500, 1);
            $player->sendTitle(Loader::getInstance()->prefix , TE::GREEN. "Vanish Activado");
            $player->sendMessage(Loader::getInstance()->prefix . TE::GREEN. "Vanish Activado");
        }else{
            unset(Loader::getInstance()->InVanish[$player->getName()]);
            foreach(Server::getInstance()->getOnlinePlayers() as $players){
                $players->showPlayer($player);
            }
            $player->sendTitle(Loader::getInstance()->prefix , TE::RED. "Vanish Desactivado");
            $player->sendMessage(Loader::getInstance()->prefix . TE::RED. "Vanish Desactivado");
            $player->getEffects()->clear();
            $this->PlaySound($player, "random.levelup", 500, 1);
        }
    }

    public function getTp(Player $pl){
        if (isset (Loader::getInstance()->targetPlayer [$pl->getName()])) {
            if (Loader::getInstance()->targetPlayer[$pl->getName()] == $pl->getName()) {
                $pl->sendMessage(Loader::getInstance()->prefix. Loader::getInstance()->config->get("TeleportMe"));
                return true;
            }
            $target = Loader::getInstance()->getServer()->getPlayerExact(Loader::getInstance()->targetPlayer [$pl->getName()]);
            if ($target instanceof Player) {
                $pl->teleport($target->getLocation());
                $pl->sendMessage(Loader::getInstance()->prefix. TE::RED."Te has teletransportado a: ".$target->getName());
            }
        }
        return false;
    }

    public function hitMute(EntityDamageEvent $event) {
        if($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();
            $victim = $event->getEntity();
            if($damager instanceof Player && $victim instanceof Player){

                if ($damager->getInventory()->getItemInHand()->getCustomName() === TE::LIGHT_PURPLE."MUTE") {
                    $event->cancel();
                    Loader::getInstance()->mutedPlayer[$damager->getName()] = $victim->getName();
                    StaffUI::getInstance()->getMuteUI($damager);

                }
            }
        }
    }

    public function OnMute(PlayerChatEvent $ev){
        $message = $ev->getMessage();
        $pl = $ev->getPlayer();

        $muteplayer = $pl->getName();
        $muteInfo = Loader::getInstance()->mt->query("SELECT * FROM plaayersMute WHERE player = '$muteplayer';");
        $array = $muteInfo->fetchArray(SQLITE3_ASSOC);

        if (!empty ($array)) {

            $muteTime = $array['muteTime'];
            $reason = $array['reason'];
            $staff = $array['staff'];
            $now = time();

            if ($muteTime > $now) {

                $remainingTime = $muteTime - $now;
                $day = floor($remainingTime / 86400);
                $hourSeconds = $remainingTime % 86400;
                $hour = floor($hourSeconds / 3600);
                $minuteSec = $hourSeconds % 3600;
                $minute = floor($minuteSec / 60);
                $remainingSec = $minuteSec % 60;
                $second = ceil($remainingSec);
                $name = $pl->getName();
                $pl->sendMessage(Loader::getInstance()->prefix. str_replace(["{player}", "{day}", "{hour}", "{minute}", "{reason}", "{staff}"], [$name
                    , $day, $hour, $minute, $reason, $staff], Loader::getInstance()->config->get("MuteChat")));

                $ev->cancel();

            } else {

                Loader::getInstance()->mt->query("DELETE FROM plaayersMute WHERE player = '$muteplayer';");

            }
        }
    }

    public function onPlayerLogin(PlayerLoginEvent $ev){
        $pl = $ev->getPlayer();
        $banplayer = $pl->getName();
        $banInfo = Loader::getInstance()->lx->query("SELECT * FROM banPlayers WHERE player = '$banplayer';");
        $array = $banInfo->fetchArray(SQLITE3_ASSOC);

        if (!empty ($array)) {

            $banTime = $array['banTime'];
            $reason = $array['reason'];
            $staff = $array['staff'];
            $now = time();

            if ($banTime > $now) {

                $remainingTime = $banTime - $now;
                $day = floor($remainingTime / 86400);
                $hourSeconds = $remainingTime % 86400;
                $hour = floor($hourSeconds / 3600);
                $minuteSec = $hourSeconds % 3600;
                $minute = floor($minuteSec / 60);
                $remainingSec = $minuteSec % 60;
                $second = ceil($remainingSec);
                $pl->kick(str_replace(["{day}", "{hour}", "{minute}", "{second}", "{reason}", "{staff}"], [$day, $hour, $minute, $second, $reason, $staff], Loader::getInstance()->config->get("LoginBanTempMessage")));


            } else {

                Loader::getInstance()->lx->query("DELETE FROM banPlayers WHERE player = '$banplayer';");

            }
        }
    }

    public function hitBanUI(EntityDamageEvent $event){
        if($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();
            $victim = $event->getEntity();
            if($damager instanceof Player && $victim instanceof Player){
                $name = $damager->getName();

                if ($damager->getInventory()->getItemInHand()->getCustomName() === TE::LIGHT_PURPLE."BANNED") {
                    $event->cancel();
                    Loader::getInstance()->targetPlayer[$damager->getName()] = $victim->getName();
                    StaffUI::getInstance()->getBanUI($damager);

                }
            }
        }
    }

    public function onMove(PlayerMoveEvent $event) : void {
        $player = $event->getPlayer();
        if(in_array($player->getName(), Loader::getInstance()->frozen)) {
            $event->cancel();
            $player->sendActionBarMessage(TE::BOLD. TE::DARK_GRAY. "[!]" . TE::RED. " CONGELADO " .TE::DARK_GRAY. "[!]");
            $player->sendTitle(TE::BOLD. TE::DARK_GRAY. "[!]" . TE::RED. " ESTAS CONGELADO " .TE::DARK_GRAY. "[!]");
        }
    }

    public function hitFreeze(EntityDamageEvent $event){
        if($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();
            $victim = $event->getEntity();
            if($damager instanceof Player && $victim instanceof Player){
                if(in_array($victim->getName(), Loader::getInstance()->frozen)) {
                    $event->cancel();
                    $damager->sendMessage(Loader::getInstance()->prefix. Loader::getInstance()->freeze . Loader::getInstance()->config->get("attack"));
                }
                if ($damager->getInventory()->getItemInHand()->getCustomName() === TE::LIGHT_PURPLE."FREEZE") {
                    $event->cancel();

                    if(in_array($victim->getName(), Loader::getInstance()->frozen)) {

                        array_splice(Loader::getInstance()->frozen, array_search($victim->getName(), Loader::getInstance()->frozen), 1);
                        $victim->sendMessage(Loader::getInstance()->prefix. Loader::getInstance()->freeze ."§eYa no estas congelado.");
                        $victim->sendTitle("§l§eYa no estas", "§cFREEZE!");
                        $victim->setNameTag(str_replace(Loader::getInstance()->freeze_tag, "", $victim->getNameTag()));
                        Loader::getInstance()->getServer()->broadcastMessage(Loader::getInstance()->prefix. Loader::getInstance()->freeze . "§e" . $victim->getName() . "§e dejó de estar congelado.");
                        $victim->sendMessage(Loader::getInstance()->prefix. Loader::getInstance()->freeze ."§eEste jugador ya estába congelado");

                    } else {
                        array_push(Loader::getInstance()->frozen, $victim->getName());
                        Loader::getInstance()->getServer()->broadcastMessage(Loader::getInstance()->prefix. Loader::getInstance()->freeze ."§e" . $victim->getName() . "§e ha sido congelado.");
                        $victim->setNameTag(Loader::getInstance()->freeze_tag.$victim->getNametag());
                        $victim->sendActionBarMessage(TE::BOLD. TE::DARK_GRAY. "[!]" . TE::RED. " CONGELADO " .TE::DARK_GRAY. "[!]");
                        $victim->sendTitle(TE::BOLD. TE::DARK_GRAY. "[!]" . TE::RED. " ESTAS CONGELADO " .TE::DARK_GRAY. "[!]");
                        $victim->sendMessage(Loader::getInstance()->prefix. Loader::getInstance()->freeze . "§eHas sido congelado, habla con el staff");
                        return true;
                    }

                }
            }
        }
        return true;
    }

    public function onAttack(EntityDamageByEntityEvent $event) : void {
        $damager = $event->getDamager();
        $entity = $event->getEntity();

        if($damager instanceof Player) {
            if(in_array($damager->getName(), Loader::getInstance()->frozen)) {

                $event->cancel();
                $damager->sendMessage(Loader::getInstance()->prefix. Loader::getInstance()->freeze . "§cNo puedes hacer esto!");

            }
        }

    }

    public function onJoinUser(PlayerJoinEvent $event) : void {
        $player = $event->getPlayer();
        foreach(Server::getInstance()->getOnlinePlayers() as $players){
            $players->showPlayer($player);
        }
        if(in_array($player->getName(), Loader::getInstance()->frozen)) {
            $player->setNameTag(Loader::getInstance()->freeze_tag.$player->getNametag());
            $player->sendMessage(Loader::getInstance()->prefix. Loader::getInstance()->freeze . "§cSigues congelado!");
        }
    }

    public function onQuit(PlayerQuitEvent $e) {
        $player = $e->getPlayer();
        Loader::getInstance()->quitStaff($player);
        foreach(Server::getInstance()->getOnlinePlayers() as $players){
            $players->showPlayer($player);
        }
        $dias = Loader::getInstance()->config->get("days");
        $horas = Loader::getInstance()->config->get("hours");
        $minutos = Loader::getInstance()->config->get("minutes");
        $razon = Loader::getInstance()->config->get("reason");
        $staff = "VERSAL-BAN";
        if(in_array($player->getName(), Loader::getInstance()->frozen)) {
            if(!Loader::getInstance()->config->get("autoban")) return false;
            else {
                $now = time();
                $day = ($dias * 86400);
                $hour = ($horas * 3600);
                if ($minutos > 1) {
                    $min = ($minutos * 60);
                } else {
                    $min = 60;
                }
                $banTime = $now + $day + $hour + $min;
                $banInfo = Loader::getInstance()->lx->prepare("INSERT OR REPLACE INTO banPlayers (player, banTime, reason, staff) VALUES (:player, :banTime, :reason, :staff);");
                $banInfo->bindValue(":player", $player->getName());
                $banInfo->bindValue(":banTime", $banTime);
                $banInfo->bindValue(":reason", $razon);
                $banInfo->bindValue(":staff", $staff);
                $banInfo->execute();
                Loader::getInstance()->getServer()->broadcastMessage(Loader::getInstance()->prefix. str_replace(["{player}", "{day}", "{hour}", "{minute}", "{reason}"], [$player->getName(), $dias, $horas, $minutos, $razon], Loader::getInstance()->config->get("serverbanmsg")));
            }
        }
        return false;
    }

    public static function PlaySound(Player $player, string $sound, int $volume, float $pitch){
        $packet = new PlaySoundPacket();
        $packet->x = $player->getPosition()->getX();
        $packet->y = $player->getPosition()->getY();
        $packet->z = $player->getPosition()->getZ();
        $packet->soundName = $sound;
        $packet->volume = $volume;
        $packet->pitch = $pitch;
        $player->getNetworkSession()->sendDataPacket($packet);
    }

}