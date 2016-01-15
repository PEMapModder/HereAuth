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

use HereAuth\User\User;

class ConfigDefinedRegistrationStep implements RegistrationStep{
	/** @type User */
	private $user;
	/** @type string */
	private $message;
	/** @type string */
	private $fieldName;
	/** @type string|null */
	private $regex;
	/** @type string|null */
	private $error;

	public function __construct(User $user, $message, $fieldName, $regex = null, $error = null){
		$this->user = $user;
		$this->message = $message;
		$this->fieldName = $fieldName;
		$this->regex = $regex;
		$this->error = $error;
	}

	public function getMessage(){
		return $this->message;
	}

	public function onSubmit($value){
		if($this->regex !== null and !preg_match($this->regex, $value)){
			$this->user->getPlayer()->sendMessage($this->error);
			return false;
		}
		$this->user->getAccountInfo()->opts->{$this->fieldName} = $value;
		return true;
	}
}
