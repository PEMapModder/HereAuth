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

namespace HereAuth\Task;

use HereAuth\HereAuth;
use pocketmine\scheduler\PluginTask;

class CheckUserTimeoutTask extends PluginTask{
	/** @type HereAuth */
	private $main;

	public function __construct(HereAuth $main){
		parent::__construct($this->main = $main);
		$this->main->getServer()->getScheduler()->scheduleRepeatingTask($this, 20);
	}

	public function onRun($currentTick){
		foreach($this->main->getUsers() as $user){
			if(!$user->isPlaying() and microtime(true) - $user->getLoadTime() >= ($timeout = $this->main->getConfig()->getNested("Login.Timeout", 120))){
				$this->main->getAuditLogger()->logTimeout(strtolower($user->getPlayer()->getName()), $user->getPlayer()->getAddress());
				$user->getPlayer()->kick("Failed to login in $timeout seconds", false);
			}
		}
	}
}
