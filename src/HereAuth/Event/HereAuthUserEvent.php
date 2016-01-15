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

use HereAuth\User\User;

abstract class HereAuthUserEvent extends HereAuthEvent{
	/** @type User */
	private $user;

	public function __construct(User $user){
		parent::__construct($user->getMain());
		$this->user = $user;
	}

	/**
	 * @return User
	 */
	public function getUser(){
		return $this->user;
	}

	/**
	 * @return \pocketmine\Player
	 */
	public function getPlayer(){
		return $this->user->getPlayer();
	}
}
