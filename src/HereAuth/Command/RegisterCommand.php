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

class RegisterCommand extends HereAuthUserCommand{
	public function __construct(HereAuth $main){
		$this->main = $main;
		parent::__construct($main, "register", "Register your account", "/register", "reg", "r");
		$this->setPermission("hereauth.register");
	}

	public function hasPerm(User $user){
		return $user->isPlaying() and !$user->getAccountInfo()->passwordHash;
	}

	protected function onRun(array $args, User $user){
		$user->startRegistration();
		return true;
	}
}
