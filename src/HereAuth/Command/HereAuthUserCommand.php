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

use HereAuth\User\User;
use pocketmine\command\CommandSender;
use pocketmine\Player;

abstract class HereAuthUserCommand extends HereAuthCommand{
	public function testPermissionSilent(CommandSender $target){
		if(!parent::testPermissionSilent($target)){
			return false;
		}
		if(!($target instanceof Player)){
			return false;
		}
		$user = $this->getMain()->getUserByPlayer($target);
		if($user === null){
			return false;
		}
		return $this->hasPerm($user);
	}

	protected function hasPerm(/** @noinspection PhpUnusedParameterInspection */
		User $user){
		return true;
	}

	protected function run(array $args, CommandSender $issuer){
		if(!($issuer instanceof Player)){
			return false;
		}
		$user = $this->getMain()->getUserByPlayer($issuer);
		if($user === null){
			return false;
		}
		return $this->onRun($args, $user);
	}

	protected abstract function onRun(array $args, User $user);
}
