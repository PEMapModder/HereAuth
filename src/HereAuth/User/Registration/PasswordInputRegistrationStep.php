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

/** @noinspection PhpInternalEntityUsedInspection */
final class PasswordInputRegistrationStep implements PasswordRegistrationStep{
	/** @type User */
	private $user;

	public function __construct(User $user){
		$this->user = $user;
	}

	public function getMessage(){
		return $this->user->getMain()->getMessages()->getNested("Register.PasswordInput", "Please type password");
	}

	public function onSubmit($value){
		if($this->validatePassword($this->user, $value)){
			$this->user->getRegistration()->setTempHash(HereAuth::hash($value, $this->user->getPlayer()));
			return true;
		}
		return false;
	}

	public static function validatePassword(User $user, $value){
		$length = strlen($value);
		$config = $user->getMain()->getConfig();
		$messages = $user->getMain()->getMessages();
		$minLength = $config->getNested("Registration.MinLength", 4);
		if($length < $minLength){
			$user->getPlayer()->sendMessage($messages->getNested("Register.PasswordUnderflow", "too short"));
			return false;
		}
		$maxLength = $config->getNested("Registration.MaxLength", -1);
		if($maxLength !== -1 and $length > $maxLength){
			$user->getPlayer()->sendMessage($messages->getNested("Register.PasswordOverflow", "too long"));
			return false;
		}
		if($config->getNested("Registration.BanPureLetters", false) and preg_match('/^[a-z]+$/i', $value)){
			$user->getPlayer()->sendMessage($messages->getNested("Register.PasswordPureLetters", "only letters"));
			return false;
		}
		if($config->getNested("Registration.BanPureNumbers", false) and preg_match('/^[0-9]+$/', $value)){
			$user->getPlayer()->sendMessage($messages->getNested("Register.PasswordPureNumbers", "only numbers"));
			return false;
		}
		if($config->getNested("Registration.DisallowSlashes", true) and $value{0} === "/"){
			$user->getPlayer()->sendMessage($messages->getNested("Register.PasswordSlashes", "do not start with slashes"));
			return false;
		}
		return true;
	}
}
