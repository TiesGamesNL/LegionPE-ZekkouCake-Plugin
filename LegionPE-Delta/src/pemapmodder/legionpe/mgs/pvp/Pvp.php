<?php

namespace pemapmodder\legionpe\mgs\pvp;

use pemapmodder\legionpe\hub\HubPlugin;
use pemapmodder\legionpe\mgs\MgMain;

use pemapmodder\utils\PluginCmdExt as Cmd;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor as CmdExe;
use pocketmine\command\CommandSender as Issuer;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\permission\DefaultPermissions as DP;
use pocketmine\permission\Permission as Perm;

class Pvp extends MgMain implements CmdExe, Listener{
	public $pvpDies = array();
	public function __construct(){
		$this->server = Server::getInstance();
		$this->hub = HubPlugin::get();
	}
	protected function regPerms(){
		if("cmd" === "cmd"){
			$mgs = $this->server->getPluginManager()->getPermission("legionpe.cmd.mg");
			$mg = DP::registerPermission(new Perm("legionpe.cmd.mg.pvp", "Allow using KitPvP minigame commands", Perm::DEFAULT_FALSE), $mgs);
			DP::registerPermission(new Perm("legionpe.cmd.mg.pvp.class", "Allow using command to choose self class in KitPvP"), $mg);
			DP::registerPermission(new Perm("legionpe.cmd.mg.pvp.pvp", "Allow using command /pvp in KitPvP minigame"), $mg); // DEFAULT_FALSE because minigame-only
		}
		if("action" === "action"){
			$mgs = $this->server->getPluginManager()->getPermission("legionpe.mg");
			$mg = DP::registerPermission(new Perm("legionpe.mg.pvp", "Allow doing some actions in PvP minigame"), $mgs);
			DP::registerPermission(new Perm("legionpe.mg.pvp.spawnattack", "Allow attacking at spawn platform", Perm::DEFAULT_OP), $mg);
		}
	}
	protected function regEvts(){
//		 $this->server->getPluginManager()->registerEvent("pocketmine\\event\\entity\\EntityDeathEvent", $this, EventPriority::HIGH, new EvtExe(array($this, "onDeath")), $this->hub);
//		 $this->server->getPluginManager()->registerEvent("pocketmine\\event\\entity\\EntityHurtEvent", $this, EventPriority::HIGH, new EvtExe(array($this, "onHurt")), $this->hub);
//		 $this->server->getPluginManager()->registerEvent("pocketmine\\event\\player\\PlayerAttackEvent", $this, EventPriority::HIGH, new EvtExe(array($this, "onAttack")), $this->hub);
//		 $this->server->getPluginManager()->registerEvent("pocketmine\\event\\player\\PlayerRespawnEvent", $this, EventPriority::HIGH, new EvtExe(array($this, "onRespawn")), $this->hub);
	}
	protected function initCmds(){
		if("pvp" === "pvp"){
			$cmd = new Cmd("pvp", $this->hub, $this);
			$cmd->setDescription("Get the PvP kit!");
			$cmd->setUsage("/pvp");
			$cmd->setPermission("legionpe.cmd.mg.pvp.pvp");
			$cmd->setAliases(array("kit"));
			$cmd->register($this->server->getCommandMap());
		}
		if("class" === "class"){
			$cmd = new Cmd("class", $this->hub, $this);
			$cmd->setUsage("/class <class>");
			$cmd->setDescription("Choose a KitPvP class");
			$cmd->setPermission("legionpe.cmd.mg.pvp.class");
			$cmd->register($this->server->getCommandMap());
		}
	}
	public function onCommand(Issuer $isr, Command $cmd, $label, array $args){
		if(!($isr instanceof Player)) return "Please run this command in-game.";
		switch("$cmd"){
			case "pvp":
				$this->equip($isr);
				return "PvP kit given!";
			case "class":
				$classes = $this->hub->getConfig()->get("kitpvp")["classes"][$this->hub->getRank($isr)];
				if(!in_array($args[0], $classes)){
					return "You don't have access to this class!";
				}
				$db = $this->hub->getDb($isr);
				$pvp = $db->get("kitpvp");
				$pvp["class"] = $args[0];
				return "Your class has been set to $args[0].";
		}
		return true;
	}
	/*public function onDeath(Event $event){
		$p = $event->getEntity();
		if(!($p instanceof Player) or $p->getLevel()->getName() !== "world_pvp") return;
		$cause = $event->getCause();
		if($cause instanceof Player){
			$this->onKill($cause);
			$cause->sendMessage("You killed {$p->getDisplayName()}!");
			$cause->sendMessage("Team points +2!");
			Team::get($this->hub->getDb($cause)->get("team"))["points"] += 2;
			$this->pvpDies[$p->getID()] = true;
			$p->sendMessage("You have been killed by {$cause->getDisplayName()}!");
		}
		Team::get($this->hub->getDb($p)->get("team"))["points"]--;
		$config = $this->hub->getDb($p);
		$data = $config->get("kitpvp");
		$data["deaths"]++;
		$config->set("kitpvp", $data);
		$config->save();
		$p->sendMessage("Your number of deaths is now {$data["deaths"]}!");
		$event->setMessage("");
	}*/
	public function onJoinMg(Player $p){
	}
	public function onQuitMg(Player $p){
	}
	public function getName(){
		return "KitPvP";
	}
	public function getSessionId(){
		return HubPlugin::PVP;
	}
	public function getDefaultChatChannel(Player $player, $tid){
		return "legionpe.chat.pvp.$tid";
	}
	public function getSpawn(Player $player, $TID){
		return RawLocs::pvpSpawn();
	}
	public function isJoinable(Player $player, $t){
		return true;
	}
	public function getPermission(){
		return "legionpe.mg.pvp";
	}
	public function getStats(Player $player, array $args = []){
		if(isset($args[0]) and strtolower($args[0]) === "top"){
			return str_replace(PHP_EOL, "\n", yaml_emit($this->hub->config->get("kitpvp")["top-kills"]));
		}
		$data = $this->hub->getDb($player)->get("kitpvp");
		$output = "Your kills: ".$data["kills"]."\n";
		$output .= "Your deaths: ".$data["deaths"]."\n";
		$output .= "Ratio: ".round($data["kills"]/$data["deaths"], 3);
		return $output;
	}
	public function onRespawn(PlayerRespawnEvent $event){
		$p = $event->getPlayer();
		if(@$this->pvpDies[$p->getID()] !== true)
			return;
		$p->teleport(RawLocs::pvpSpawn());
		$this->equip($p);
		$this->pvpDies[$p->getID()] = false;
		unset($this->pvpDies[$p->getID()]);
	}
	/*public function onHurt(Event $event){
		$p = $event->getEntity();
		if(!($p instanceof Player)) return;
		$cause = $event->getCause();
		if(in_array($cause, array("suffocation", "falling")))
			$event->setCancelled(true);
	}*/
	/*public function onAttack(Event $event){
		$p = $event->getPlayer();
		if($p instanceof Player){
			if((RawLocs::safeArea()->isInside($p) or RawLocs::safeArea()->isInside($event->getVictim()))and !$p->hasPermission("legionpe.mg.pvp.spawnattack")){
				$event->setCancelled(true);
				$p->sendMessage("You may not attack people here!");
			}
			elseif($this->hub->getTeam($p) === $this->hub->getTeam($event->getVictim())){
				$event->setCancelled(true);
			}
		}
	}*/
	public function onKill(Player $killer){
		$db = $this->hub->getDb($killer);
		$data = $db->get("kitpvp");
		$data["kills"]++;
		$db->set("kitpvp", $data);
		$db->save();
		$killer->sendMessage("Your number of kills is now {$data["kills"]}!");
		$killer->heal($data["kills"] > 1000 ? 4:2);
		$this->updatePrefix($killer, $data["kills"]);
	}
	protected function updatePrefix(Player $killer, $kills){
		// update top kills
		$data = $this->hub->config->get("kitpvp");
		$tops = $data["top-kills"];
		$tmp = array(strtolower($killer->getName()), $killer->getDisplayName());
		$tmp2 = array(strtolower($killer->getName()), $kills);
		foreach($tops as $name=>$cnt){
			$tmp[strtolower($name)] = $name;
			$tmp2[strtolower($name)] = $cnt;
		}
		arsort($tmp2, SORT_NUMERIC);
		$tops = array();
		foreach(array_slice($tmp2, 0, 5, true) as $key=>$cnt)
			$tops[$tmp[$key]] = $cnt;
		$data["top-kills"] = $tops;
		$this->hub->config->set("kitpvp", $data);
		$this->hub->config->save();
		// prepare personal prefix
		$pfxs = $this->hub->config->get("kitpvp")["prefixes"];
		asort($pfxs, SORT_NUMERIC);
		$pfx = "";
		foreach($pfxs as $prefix=>$min){
			if($kills >= $min){
				$pfx = $prefix;
			}
			else{
				break;
			}
		}
		// set personal prefix
		$data = $this->hub->getDb($killer)->get("prefixes");
		$data["kitpvp"] = $pfx;
		$data["kitpvp-kills"] = $kills;
		if(isset($tops[$killer->getDisplayName()]))
			$data["kitpvp-rank"] = "#".(array_search($killer->getDisplayName(), array_keys($tops)) + 1);
		$this->hub->getDb($killer)->set("prefixes", $data);
		$this->hub->getDb($killer)->save();
	}
	public function equip(Player $player){
		$rk = $this->hub->getDb($player)->get("kitpvp")["class"];
		$data = $this->hub->config->get("kitpvp")["auto-equip"][$rk];
		foreach($data["inv"] as $slot=>$item){
			$player->getInventory()->setItem($slot, Item::get($item[0], $item[1], $item[2]));
		}
		foreach($data["arm"] as $slot=>$armor){
			$player->getInventory()->setItem($player->getInventory()->getSize() + ($slot & 0b11), Item::get($armor));
		}
	}
	public static function init(){
		HubPlugin::get()->statics[get_class()] = new static();
	}
	public static function get(){
		return HubPlugin::get()->statics[get_class()];
	}
}
