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
use pocketmine\event\Cancellable;

class HereAuthLoginEvent extends HereAuthUserEvent implements Cancellable{
	public static $handlerList = null;

	const METHOD_CLIENT_SECRET = "secret";
	const METHOD_UUID = "uuid";
	const METHOD_IP = "ip";
	const METHOD_PASSWORD = "password";

	/** @type string */
	private $method;

	public function __construct(User $user, $method){
		parent::__construct($user);
		$this->method = $method;
	}

	/**
	 * @return string
	 */
	public function getMethod(){
		return $this->method;
	}
}
