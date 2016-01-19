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

namespace HereAuth\Command;

use HereAuth\HereAuth;
use HereAuth\User\User;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;

class OptCommand extends HereAuthUserCommand{
	public function __construct(HereAuth $main){
		parent::__construct($main, "auth", "Change/View HereAuth options for yourself", "/auth <type> <value>, or /auth [page]", "opt");
	}

	protected function onRun(array $args, User $user){
		if(!isset($args[1])){
			$page = isset($args[0]) ? ((int) $args[0]) + 1 : 1;
			return HereAuth::page($this->getHelpMessage($user), $page);
		}
		$opts = $user->getAccountInfo()->opts;
		$type = array_shift($args);
		$value = array_shift($args);
		$boolVal = $this->parseBool($value);
		$action = $boolVal ? "Enabled" : "Disabled";
		switch($type){
			case "autosecret":
			case "as":
				if(!$user->getPlayer()->hasPermission("hereauth.auth.autoauth.clientsecret")){
					return "You don't have permission to do this!";
				}
				$opts->autoSecret = $boolVal;
				return "$action client secret AutoAuth";
			case "autouuid":
			case "au":
				if(!$user->getPlayer()->hasPermission("hereauth.auth.autoauth.uuid")){
					return "You don't have permission to do this!";
				}
				$opts->autoUuid = $boolVal;
				return "$action UUID AutoAuth";
			case "autoip":
			case "ai":
				if(!$user->getPlayer()->hasPermission("hereauth.auth.autoauth.ip")){
					return "You don't have permission to do this!";
				}
				$opts->autoIp = $boolVal;
				return "$action IP AutoAuth";
			case "maskloc":
			case "ml":
				if(!$user->getPlayer()->hasPermission("hereauth.auth.masking.location.toggle")){
					return "You don't have permission to do this!";
				}
				$opts->maskLoc = $boolVal;
				return "$action location masking";
			case "maskinv":
			case "mi":
				if(!$user->getPlayer()->hasPermission("hereauth.auth.masking.inventory")){
					return "You don't have permission to do this!";
				}
				$opts->maskInv = $boolVal;
				return "$action inventory masking";
			case "mafskin":
			case "mafs":
				if(!$user->getPlayer()->hasPermission("hereauth.auth.multiauth.skin")){
					return "You don't have permission to do this!";
				}
				$opts->multiSkin = $boolVal;
				return "$action skin MAF";
			case "mafip":
			case "mafi":
				if(!$user->getPlayer()->hasPermission("hereauth.auth.multiauth.ip")){
					return "You don't have permission to do this!";
				}
				$opts->multiIp = $boolVal;
				return "$action IP MAF";
		}
		return "Unknown option \"$type\"";
	}

	private function getHelpMessage(User $user){
		$opts = $user->getAccountInfo()->opts;
		$output = "Your HereAuth options:\n";
		$mlp = $opts->maskLocPos;
		if(!preg_match(/** @lang RegExp */
			'#^((\?spawn\?)|((\-)?[0-9]+,(\-)?[0-9]+,(\-)?[0-9]+))@([^/\\\\]+)$#', $mlp, $match)
		){
			$maskLocString = "none";
		}else{
			$pos = $match[1];
			$world = $match[7];
			if($pos === "?spawn?"){
				$maskLocString = "spawn";
			}else{
				$maskLocString = "($pos)";
			}
			$maskLocString .= " in ";
			if($world === "?default?"){
				$maskLocString .= " default world";
			}elseif($world === "?current?"){
				$maskLocString .= " current world";
			}else{
				$maskLocString .= " world \"$world\"";
			}
		}
		$opts = [
			"AutoAuth through client secret" => $opts->autoSecret,
			"AutoAuth through UUID" => $opts->autoUuid,
			"AutoAuth through IP address" => $opts->autoIp,
			"Location masking" => $opts->maskLoc,
			"Location masking position" => $maskLocString,
			"Inventory masking" => $opts->maskInv,
			"Multi-factor auth (MFA) through skin" => $opts->multiSkin,
			"MFA through IP address" => $opts->multiIp,
		];
		foreach($opts as $key => $value){
			$output .= TextFormat::GOLD . $key . ": ";
			$output .= TextFormat::RED . $this->stringify($value) . "\n";
		}
		$output .= TextFormat::AQUA . "====================\n";
		$output .= TextFormat::LIGHT_PURPLE . "To change these values:\n";
		$output .= "/auth as on|off ";
		$output .= TextFormat::GREEN . "Toggle AutoAuth through " . TextFormat::YELLOW . "client secret\n";
		$output .= "/auth au on|off ";
		$output .= TextFormat::GREEN . "Toggle AutoAuth through " . TextFormat::YELLOW . "UUID\n";
		$output .= "/auth ai on|off ";
		$output .= TextFormat::GREEN . "Toggle AutoAuth through " . TextFormat::YELLOW . "IP\n";
		$output .= "/auth ml on|off ";
		$output .= TextFormat::GREEN . "Toggle " . TextFormat::YELLOW . "location masking\n";
		$output .= "/auth mlt here|<x,y,z@world> ";
		$output .= TextFormat::GREEN . "Set " . TextFormat::YELLOW . "location masking\n";
		$output .= "/auth mi on|off ";
		$output .= TextFormat::GREEN . "Toggle " . TextFormat::YELLOW . "inventory masking\n";
		$output .= "/auth mafs on|off";
		$output .= TextFormat::GREEN . "Toggle " . TextFormat::YELLOW . "skin MAF (multi-factor authentication)\n";
		$output .= "/auth mafi on|off";
		$output .= TextFormat::GREEN . "Toggle " . TextFormat::YELLOW . "IP MAF (multi-factor authentication)\n";
		return $output;
	}

	private function stringify($value){
		if(is_bool($value)){
			return $value ? "enabled" : "disabled";
		}
		if($value instanceof Position){
			$world = $value->isValid() ? ("world \"" . $value->getLevel()->getName() . "\"") : "current world";
			return "(" . $value->x . "," . $value->y . "," . $value->z . ") in " . $world;
		}
		if($value instanceof Vector3){
			return "(" . $value->x . "," . $value->y . "," . $value->z . ")";
		}
		return (string) $value;
	}

	private function parseBool($string){
		return ($string === "t" or
			$string === "true" or
			$string === "correct" or
			$string === "enable" or
			$string === "on" or
			$string === "i" or
			$string === "y" or
			$string === "yes");
	}
}
