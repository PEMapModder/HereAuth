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

use pocketmine\scheduler\AsyncTask;

abstract class AsyncQueryTask extends AsyncTask{
	const MYSQLI_KEY = "HereAuth.Database.MySQL.Async.MySQLi";

	/** @type MySQLCredentials */
	private $credentials;

	protected function __construct(MySQLCredentials $credentials){
		$this->credentials = $credentials;
	}

	/**
	 * @return \mysqli|null
	 */
	protected function getMysqli(){
		$mysqli = $this->getFromThreadStore(self::MYSQLI_KEY);
		if($mysqli !== null){
			return $mysqli;
		}
		$mysqli = MySQLDatabase::createMysqliInstance($this->credentials);
		$this->saveToThreadStore(self::MYSQLI_KEY, $mysqli);
		return $mysqli;
	}
}
