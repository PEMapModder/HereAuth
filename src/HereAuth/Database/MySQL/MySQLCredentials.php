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

use pocketmine\utils\Config;

class MySQLCredentials{
	/** @type string */
	public $host;
	/** @type int */
	public $port;
	/** @type string */
	public $username;
	/** @type string */
	public $password;
	/** @type string */
	public $schema;
	/** @type string */
	public $socket;

	public static function fromConfig(Config $config){
		$cred = new MySQLCredentials;
		$cred->host = $config->getNested("Database.MySQL.Connection.Address", "example.com");
		$cred->port = $config->getNested("Database.MySQL.Connection.Port", 3306);
		$cred->username = $config->getNested("Database.MySQL.Connection.Username", "root");
		$cred->password = $config->getNested("Database.MySQL.Connection.Password", "");
		$cred->schema = $config->getNested("Database.MySQL.Connection.Schema", "hereauth");
		$cred->socket = $config->getNested("Database.MySQL.Connection.Socket", "");
		return $cred;
	}

	public function __toString(){
		return "MySQL database at $this->username@$this->host:$this->port/$this->schema" . ($this->socket !== "" ? " (through socket $this->socket)" : "");
	}

	public function __debugInfo(){
		return [
			"host" => $this->host,
			"port" => $this->port,
			"username" => $this->username,
			"password" => "{hashed in debug} *" . sha1(sha1($this->password, true)), // ref: http://www.pythian.com/blog/hashing-algorithm-in-mysql-password-2/
			"schema" => $this->schema,
			"socket" => $this->socket,
		];
	}
}
