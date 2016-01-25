<?php

/*
 * HereAuth
 *
 * Copyright (C) 2016 PEMapModder
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PEMapModder
 */

namespace HereAuth\User;

use HereAuth\HereAuth;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use Serializable;
use stdClass;

class AccountOpts extends stdClass implements Serializable{
	/** @type bool */
	public $autoSecret;
	/** @type bool */
	public $autoIp;
	/** @type bool */
	public $autoUuid;
	/** @type bool */
	public $maskLoc;
	/** @type string */
	public $maskLocPos;
	/** @type bool */
	public $maskInv;
	// TODO: mask chat messages through packets
	/** @type bool */
	public $multiSkin;
	/** @type bool */
	public $multiIp;
	/** @type int */
	public $multiTimeout;

	public static function defaultInstance(HereAuth $main){
		$opts = new self;
		$opts->autoSecret = $main->getConfig()->getNested("DefaultSettings.AutoAuth.ClientSecretAuth", true);
		$opts->autoIp = $main->getConfig()->getNested("DefaultSettings.AutoAuth.IPAuth", false);
		$opts->autoUuid = $main->getConfig()->getNested("DefaultSettings.AutoAuth.UUIDAuth", false);
		$opts->maskLoc = $main->getConfig()->getNested("DefaultSettings.Masking.Location.Enabled", false);
		$opts->maskLocPos = $main->getConfig()->getNested("DefaultSettings.Masking.Location.Value", "?spawn?@?current?");
		if(!preg_match(/** @lang RegExp */
			'#^((\?spawn\?)|((\-)?[0-9]+,(\-)?[0-9]+,(\-)?[0-9]+))@[^/\\\\]+$#', $opts->maskLocPos)
		){
			$main->getLogger()->alert("Incorrect syntax for location-masking position (DefaultSettings.Masking.Location.Value)! Assuming as \"?spawn?@?current?\".");
			$opts->maskLocPos = "?spawn?@?current?";
		}
		$opts->maskInv = $main->getConfig()->getNested("DefaultSettings.Masking.Inventory", true);
		$opts->multiSkin = $main->getConfig()->getNested("DefaultSettings.MultiAuth.Skin", false);
		$opts->multiIp = $main->getConfig()->getNested("DefaultSettings.MultiAuth.IP", false);
		$opts->multiTimeout = $main->getConfig()->getNested("DefaultSettings.MultiAuthTimeout", 14);
		return $opts;
	}

	public function getMaskLocation(Player $player, $ignoreMasking = false, &$warnings = []){
		// always return player current location if there is an error
		if(!$ignoreMasking and !$this->maskLoc){
			$warnings[] = "Location masking disabled";
			return $player->getLocation();
		}
		if(!preg_match(/** @lang RegExp */
			'#^((\?spawn\?)|((\-)?[0-9]+,(\-)?[0-9]+,(\-)?[0-9]+))@([^/\\\\]+)$#', $this->maskLocPos, $match)
		){
			$warnings[] = "Invalid location format";
			return $player->getLocation();
		}
		$pos = $match[1];
		$world = $match[7];
		$level = $player->getLevel();
		if($world === "?default?"){
			$level = $player->getServer()->getDefaultLevel();
		}elseif($world !== "?current?"){
			$level = $player->getServer()->getLevelByName($world);
			if(!($level instanceof Level)){
				$level = $player->getLevel();
			}
		}
		if($pos === "?spawn?"){
			$position = $level->getSpawnLocation();
		}else{
			list($x, $y, $z) = explode(",", $pos);
			$position = new Position((int) $x, (int) $y, (int) $z, $level);
		}
		return $position;
	}

	public function serialize(){
		return json_encode($this);
	}

	public function unserialize($serialized){
		$this->extractObject(json_decode($serialized));
	}

	public function extractObject(stdClass $data){
		foreach($data as $key => $value){
			$this->{$key} = $value;
		}
		return $this;
	}
}
