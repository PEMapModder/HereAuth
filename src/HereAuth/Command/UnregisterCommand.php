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

use HereAuth\Event\HereAuthUnregisterEvent;
use HereAuth\HereAuth;
use pocketmine\command\CommandSender;

class UnregisterCommand extends HereAuthCommand{
	public function __construct(HereAuth $main){
		$this->main = $main;
		parent::__construct($main, "unregister", "Unregister an account", "/unregister <account full name>", "unreg");
		$this->setPermission("hereauth.unregister");
	}

	protected function run(array $args, CommandSender $sender){
		if(!isset($args[0])){
			return "Usage: " . $this->getUsage();
		}
		$user = $this->getMain()->getUserByExactName($name = $args[0]);
		$this->getMain()->getServer()->getPluginManager()->callEvent($ev = new HereAuthUnregisterEvent($this->getMain(), $sender, $name, $user));
		if($ev->isCancelled()){
			return $ev->getCancelMessage();
		}
		if($user !== null){
			$user->resetAccount(function ($success) use ($sender, $name){
				$sender->sendMessage($success ? "Account $name has been unregistered." : "Account $name does not exist.");
			});
		}else{
			$this->getMain()->getDataBase()->unregisterAccount($name, function ($success) use ($sender, $name){
				$sender->sendMessage($success ? "Account $name has been unregistered." : "Account $name does not exist.");
			});
		}
		return "Processing...";
	}
}
