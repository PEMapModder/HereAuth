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

use HereAuth\Database\Database;
use HereAuth\HereAuth;
use pocketmine\command\CommandSender;

class RenameCommand extends HereAuthCommand{
	public function __construct(HereAuth $main){
		$this->main = $main;
		parent::__construct($main, "rename", "Rename an account", "/rename <old name> <new name>");
		$this->setPermission("hereauth.rename");
	}

	public function run(array $args, CommandSender $sender){
		if(!isset($args[1])){
			return "Usage: " . $this->getUsage();
		}
//		$user = $this->getMain()->getUserByExactName($name=$args[0]);
//		if($user !== null){
//			$info = $user->getAccountInfo();
//			$user->resetAccount();
//			$oldName = strtolower($info->name);
//			$info->name = strtolower($args[1]);
//			if(!isset($info->multiHash["nonhash:salt"])){
//				$info->multiHash["nonhash:salt"] = $oldName;
//			}
//			if($info->passwordHash{0} !== "{"){
//				$info->multiHash = ["renamed;$oldName" => $info->passwordHash];
//			}else{
//				$info->passwordHash = "{RENAMED}";
//			}
//			$this->getMain()->getDataBase()->saveData($info, false);
//			return "Account $args[0] is changed to $args[1]";
//		}
		$this->getMain()->getDataBase()->renameAccount($args[0], $args[1], function ($result) use ($args, $sender){
			if($result === Database::RENAME_SOURCE_ABSENT){
				$sender->sendMessage("'$args[0]' is not a registered account on this server");
			}elseif($result === Database::RENAME_TARGET_PRESENT){
				$sender->sendMessage("The account '$args[1]' already exists.");
			}elseif($result === Database::UNKNOWN_ERROR){
				$sender->sendMessage("An unknown error occurred");
			}else{
				$user = $this->getMain()->getUserByExactName($args[0]);
				if($user !== null){
					$user->resetAccount();
					$user->getPlayer()->kick("You have been renamed to $args[1]", false);
				}
				$sender->sendMessage("Account $args[0] renamed to $args[1]");
			}
		});
		return "Processing...";
	}
}
