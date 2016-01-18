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
use pocketmine\command\CommandSender;

class UnregisterCommand extends HereAuthCommand{
	public function __construct(HereAuth $main){
		parent::__construct($main, "unregister", "Unregister an account", "/unregister <account full name>", "unreg");
		$this->setPermission("hereauth.unregister");
	}

	protected function run(array $args, CommandSender $sender){
		if(!isset($args[0])){
			return "Usage: " . $this->getUsage();
		}
		$this->getMain()->getDatabase()->unregisterAccount($name= $args[0], function ($success) use ($sender, $name){
			$sender->sendMessage($success ? "Account $name has been unregistered." : "Account $name does not exist.");
		});
		return "Processing...";
	}
}
