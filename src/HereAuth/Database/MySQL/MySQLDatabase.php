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

namespace HereAuth\Database\MySQL;

use HereAuth\Database\Database;
use HereAuth\HereAuth;
use HereAuth\User\AccountInfo;

class MySQLDatabase implements Database{
	/** @type HereAuth */
	private $main;

	public function __construct(HereAuth $main){
		$this->main = $main;
	}

	public function loadFor($name, $identifier){
		// TODO: Implement loadFor() method.
	}

	public function saveData($name, AccountInfo $info){
		// TODO: Implement saveData() method.
	}

	public function renameAccount($oldName, $newName){
		// TODO: Implement renameAccount() method.
	}

	public function unregisterAccount($name){
		// TODO: Implement unregisterAccount() method.
	}

	public function close(){
		// TODO: Implement close() method.
	}
}
