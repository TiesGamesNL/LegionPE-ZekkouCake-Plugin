<?php

namespace pemapmodder\legionpe\geog;

use pemapmodder\utils\spaces\CuboidSpace as MySpace;

use pocketmine\math\Vector3;
use pocketmine\Server;

abstract class RawLocs{
	public final static function chooseTeamStd(){
		return new Position(165.5, 47, 67.5, self::hub());
	}
	public final static function chooseTeamSign($team){
		return new Position(166 - $team, 48, 60, self::hub());
	}
	public final static function teamScoreBarX($team){
		return 120 + $team;
	}
	public final static function teamScoreBarY(){
		return array(65, 55);
	}
	public final static function teamScoreBarZ(){
		return array(109, 140);
	}
	public final static function teamScoreBar($team, $percentage){
		$z = self::teamScoreBarZ();
		$maxLength = abs($z[0] - $z[1]);
		$length = (int) ($maxLength / 100 * $percentage);
		// console(class_exists("pemapmodder_dep\\utils\\spaces\\CuboidSpace", true) ? (Font::GREEN."CuboidSpace found"):(Font::RED."CuboidSpace Not found"));
		return new MySpace(
			new Vector3(self::teamScoreBarX($team), self::teamScoreBarY()[0], min($z)),
			new Vector3(self::teamScoreBarX($team), self::teamScoreBarY()[1], min($z) + $length),
			self::hub());
	}
	public final static function enterPvpPor(){
		return new MySpace(new Vector3(159, 30, 124), new Vector3(157, 42, 134), self::hub());
	}
	public final static function enterPkPor(){
		return new MySpace(new Vector3(134, 30, 157), new Vector3(124, 42, 159), self::hub());
	}
	public final static function enterSpleefPor(){
		return new MySpace(new Vector3(101, 30, 124), new Vector3(99, 42, 134), self::spleef());
	}
	public final static function spleefSigns(){
		return new MySpace(new Vector3(100, 31, 125), new Vector3(100, 32, 133), self::hub());
	}
	public final static function hub(){
		return self::getLevel("world");
	}
	public final static function spawn(){
		return new Position(129, 33, 129, self::hub());
	}
	public final static function spleef(){
		return self::getLevel("world_spleef");
	}
	public final static function spleefSpawn(){
		return new Position(128, 81, 123, self::spleef());
	}
	public final static function parkour(){
		return self::getLevel("world_parkour");
	}
	public final static function pk(){
		return self::parkour();
	}
	public final static function pkSpawn(){
		return self::pk()->getSafeSpawn();
	}
	public final static function pvp(){
		return self::getLevel("world_pvp");
	}
	public final static function pvpSpawn(){
		return self::pvp()->getSafeSpawn();
	}
	public final static function enterCtfPor(){
		return new MySpace(new Vector3(134, 30, 101), new Vector3(124, 42, 99), self::hub());
	}
	public final static function getLevel($name){
		foreach(Server::getInstance()->getLevels() as $level){
			if($level->getName() === $name){
				return $level;
			}
		}
		return false;
	}
}
