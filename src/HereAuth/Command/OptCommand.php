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
		$this->main = $main;
		parent::__construct($main, "auth",
			$this->getMessage("Commands.Opt.Description", "Change/View HereAuth options for yourself"),
			$this->getMessage("Commands.Opt.Usage", "/opt <type> <value>, or /opt [page]"), "opt");
	}

	protected function onRun(array $args, User $user){
		if(!isset($args[1])){
			$pageNo = (int) ($args[0] ?? 1);
			$page = $this->getMain()->page($this->getHelpMessage($user), $pageNo, $maxPages);
			$out = TextFormat::GREEN . "Showing /opt page $pageNo of $maxPages:\n" . $page;
			if($pageNo < $maxPages){
				/** @noinspection PhpWrongStringConcatenationInspection */
				$out .= "\n" . TextFormat::GREEN . "Execute " . TextFormat::WHITE . "/opt " . ($pageNo + 1) . TextFormat::GREEN . " for more";
			}
			return $out;
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
			case "mlt":
			case "mltarg":
				$player = $user->getPlayer();
				if(!$player->hasPermission("hereauth.auth.masking.location.target")){
					return "You don't have permission to do this!";
				}
				if($value === "here"){
					$value = "$player->x,$player->y,$player->z@" . $player->getLevel()->getName();
				}elseif($value === "spawn"){
					$value = "?spawn?@?current?";
				}
				$oldValue = $user->getAccountInfo()->opts->maskLocPos;
				$user->getAccountInfo()->opts->maskLocPos = $value;
				$loc = $user->getAccountInfo()->opts->getMaskLocation($player, true, $errors);
				if(count($errors) > 0){
					foreach($errors as $error){
						$player->sendMessage("Error resolving your position: " . $error);
					}
					$user->getAccountInfo()->opts->maskLocPos = $oldValue;
					return "Aborted";
				}
				return "Changed location masking target to " . "$loc->x:$loc->y:$loc->z in " . $loc->getLevel()->getName();
			case "maskinv":
			case "mi":
				if(!$user->getPlayer()->hasPermission("hereauth.auth.masking.inventory")){
					return "You don't have permission to do this!";
				}
				$opts->maskInv = $boolVal;
				return "$action inventory masking";
			case "mfaskin":
			case "mfas":
				if(!$user->getPlayer()->hasPermission("hereauth.auth.multiauth.skin")){
					return "You don't have permission to do this!";
				}
				$opts->multiSkin = $boolVal;
				return "$action skin MFA";
			case "mfaip":
			case "mfai":
				if(!$user->getPlayer()->hasPermission("hereauth.auth.multiauth.ip")){
					return "You don't have permission to do this!";
				}
				$opts->multiIp = $boolVal;
				return "$action IP MFA";
			case "mfat":
			case "mfatime":
				if($value === "forever" or $value === "infinity"){
					$value = -1;
				}else{
					$value = ((int) $value) * 86400;
				}
				$tier = "medium";
				if($value === -1 or $value >= $this->getMain()->getConfig()->getNested("Commands.MultiAuth.BigBound", 30)){
					$tier = "big";
				}elseif($value <= $this->getMain()->getConfig()->getNested("Commands.MultiAuth.SmallBound", 1)){
					$tier = "small";
				}
				$user->getAccountInfo()->opts->multiTimeout = $value;
				if(!$user->getPlayer()->hasPermission("hereauth.auth.multiauth.timeout.$tier")){
					return "You don't have permission to change your MFA timeout to a $tier number!";
				}
				return "Changed MFA timeout to " . ($value === -1 ? "forever" : (round($value / 86400, 1) . " day(s)"));
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
		$optMap = [
			"AutoAuth through client secret" => $opts->autoSecret,
			"AutoAuth through UUID" => $opts->autoUuid,
			"AutoAuth through IP address" => $opts->autoIp,
			"Location masking" => $opts->maskLoc,
			"Location masking position" => $maskLocString,
			"Inventory masking" => $opts->maskInv,
			"Multi-factor auth (MFA) through skin" => $opts->multiSkin,
			"MFA through IP address" => $opts->multiIp,
			"MFA timeout" => ($opts->multiTimeout === -1) ? "forever" : ($opts->multiTimeout . " day(s)"),
		];
		foreach($optMap as $key => $value){
			$output .= TextFormat::GOLD . $key . ": ";
			$output .= TextFormat::RED . $this->stringify($value) . "\n";
		}
		$output .= TextFormat::AQUA . "====================\n";
		$output .= TextFormat::LIGHT_PURPLE . "To change these values:\n";
		$output .= "/opt as on|off ";
		$output .= TextFormat::GREEN . "Toggle AutoAuth through " . TextFormat::YELLOW . "client secret\n";
		$output .= "/opt au on|off ";
		$output .= TextFormat::GREEN . "Toggle AutoAuth through " . TextFormat::YELLOW . "UUID\n";
		$output .= "/opt ai on|off ";
		$output .= TextFormat::GREEN . "Toggle AutoAuth through " . TextFormat::YELLOW . "IP\n";
		$output .= "/opt ml on|off ";
		$output .= TextFormat::GREEN . "Toggle " . TextFormat::YELLOW . "location " . TextFormat::GREEN . "masking\n";
		$output .= "/opt mlt here|<x,y,z@world> ";
		$output .= TextFormat::GREEN . "Set " . TextFormat::YELLOW . "location " . TextFormat::GREEN . "masking";
		$output .= "\n";
		$output .= "/opt mi on|off ";
		$output .= TextFormat::GREEN . "Toggle " . TextFormat::YELLOW . "inventory " . TextFormat::GREEN . "masking\n";
		$output .= "/opt mfas on|off ";
		$output .= TextFormat::GREEN . "Toggle " . TextFormat::YELLOW . "skin MFA (multi-factor authentication)\n";
		$output .= "/opt mfai on|off ";
		$output .= TextFormat::GREEN . "Toggle " . TextFormat::YELLOW . "IP MFA\n";
		$output .= "/opt mfat <timeout|forever> ";
		$output .= TextFormat::GREEN . "Set " . TextFormat::YELLOW . "MFA timeout in days " . TextFormat::GREEN . "(or \"forever\")\n";
//		$output .= "If /opt doesn't work, try /auth instead"; // <-- how would people even be able to execute this command if it doesn't work?
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
		return (
			$string === "t" or
			$string === "true" or
			$string === "right" or
			$string === "correct" or // OCD
			$string === "enable" or
			$string === "on" or
			$string === "i" or
			$string === "1" or
			$string === "y" or
			$string === "yes"
		);
	}
}
