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

namespace HereAuth\Event;

use HereAuth\User\Registration\Registration;
use HereAuth\User\User;

class HereAuthRegistrationCreationEvent extends HereAuthUserEvent{
	public static $handlerList = null;

	/** @type Registration */
	private $registration;

	public function __construct(User $user){
		parent::__construct($user);
		$this->registration = new Registration($user);
	}

	/**
	 * @return Registration
	 */
	public function getRegistration(){
		return $this->registration;
	}
}
