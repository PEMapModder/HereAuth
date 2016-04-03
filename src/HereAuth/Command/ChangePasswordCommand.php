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
use HereAuth\User\Registration\PasswordInputRegistrationStep;
use HereAuth\User\User;

class ChangePasswordCommand extends HereAuthUserCommand{
	public function __construct(HereAuth $main){
		$this->main = $main;
		parent::__construct($main, "changepassword",
			$this->getMessage("Commands.ChangePassword.Description", "Change your password"),
			$this->getMessage("Commands.ChangePassword.Usage", "/chpw <new password>"),
			"chpw", "changepw", "chgpw");
		$this->setPermission("hereauth.changepw");
	}

	protected function hasPerm(User $user){
		return (bool) $user->getAccountInfo()->passwordHash;
	}

	protected function onRun(array $args, User $user){
		if(!isset($args[0])){
			return "Usage: " . $this->getUsage();
		}
		$password = $args[0];
		$hash = HereAuth::hash($password, $user->getPlayer());
		$firstHash = $user->getChangepwHash();
		if($firstHash !== null){
			$user->setChangepwHash(null);
			if($firstHash === $hash){
				$user->getAccountInfo()->passwordHash = $hash;
				return $this->getMessage("Commands.ChangePassword.Success", "Your password has been changed.");
			}
			return $this->getMessage("Commands.ChangePassword.DoubleCheckFailure", "Your password is different this time! Aborted.");
		}
		if(!PasswordInputRegistrationStep::validatePassword($user, $password)){
			return false;
		}
		$user->setChangepwHash($hash);
		return $this->getMessage("Commands.ChangePassword.RequestRepeat", "Please run this command again to confirm.");
	}
}
