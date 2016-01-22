<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace HereAuth\Task;

use HereAuth\HereAuth;
use pocketmine\scheduler\PluginTask;

class RemindLoginTask extends PluginTask{
	/** @type HereAuth $main */
	private $main;
	/** @type string */
	private $type;

	public function __construct(HereAuth $main){
		parent::__construct($this->main = $main);
		$period = (int) ($main->getConfig()->getNested("RemindLogin.Interval", 0.5) * 20);
		$this->type = strtolower($main->getConfig()->getNested("RemindLogin.Type", "popup"));
		if($this->type === "none"){
			return;
		}
		$main->getServer()->getScheduler()->scheduleDelayedRepeatingTask($this, $period, $period);
	}

	public function onRun($currentTick){
		$reg = $this->main->getConfig()->getNested("RemindLogin.Message.Register", "Register please");
		$log = $this->main->getConfig()->getNested("RemindLogin.Message.Login", "Login please");
		if($this->type === "chat"){
			$fx = "sendMessage";
		}elseif($this->type === "tip"){
			$fx = "sendTip";
		}else{
			$fx = "sendPopup";
		}
		foreach($this->main->getUsers() as $user){
			if($user->isRegistering()){
				$user->getPlayer()->$fx($reg);
			}elseif($user->isLoggingIn()){
				$user->getPlayer()->$fx($log);
			}
		}
	}
}
