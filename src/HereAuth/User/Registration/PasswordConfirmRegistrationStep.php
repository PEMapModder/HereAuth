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

namespace HereAuth\User\Registration;

use HereAuth\HereAuth;
use HereAuth\User\User;
use pocketmine\event\TextContainer;

/** @noinspection PhpInternalEntityUsedInspection */
final class PasswordConfirmRegistrationStep implements PasswordRegistrationStep{
	/** @type User */
	private $user;

	public function __construct(User $user){
		$this->user = $user;
	}

	/**
	 * @return string|TextContainer
	 */
	public function getMessage(){
		return $this->user->getMain()->getMessages()->getNested("Register.PasswordConfirm", "Please repeat");
	}

	public function onSubmit($value){
		$hash = HereAuth::hash($value, $this->user->getPlayer());
		$tempHash = $this->user->getRegistration()->getTempHash();
		$this->user->getRegistration()->setTempHash("");
		if($hash !== $tempHash){
			$this->user->getPlayer()->sendMessage($this->user->getMain()->getMessages()->getNested("Register.PasswordMismatch", "Incorrect password"));
			$this->user->getRegistration()->rewind();
			return false;
		}
		$this->user->getAccountInfo()->passwordHash = $hash;
		return true;
	}
}
