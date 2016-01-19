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
	/** @type MySQLCredentials */
	private $connParams;

	/** @type string */
	private $mainTable;

	public function __construct(HereAuth $main){
		$this->main = $main;
		$this->connParams = MySQLCredentials::fromConfig($main->getConfig());
		$this->mainTable = $main->getConfig()->getNested("Database.MySQL.TablePrefix", "hereauth_");
		$main->getLogger()->info("Initializing database...");
		$db = $this->createMysqliInstance($this->connParams);
		$db->query("CREATE TABLE IF NOT EXISTS `$this->mainTable` (
			name VARCHAR(63) PRIMARY KEY, hash BINARY(64),
			register INT, login INT, ip VARCHAR(50), secret BINARY(16), uuid BINARY(16),
			skin, opts, multihash)");
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

	public function unregisterAccount($name, callable $hook){
		// TODO: Implement unregisterAccount() method.
	}

	public function close(){
		// TODO: Implement close() method.
	}

	public static function createMysqliInstance(MySQLCredentials $cred){
		/** @noinspection PhpUsageOfSilenceOperatorInspection */
		$db = @new \mysqli($cred->host, $cred->username, $cred->password, $cred->schema, $cred->port, $cred->socket);
		if($db->connect_error === "Unknown database '$cred->schema'"){
			$db = @new \mysqli($cred->host, $cred->username, $cred->password, "", $cred->port, $cred->socket);
			$createSchema = true;
		}
		if($db->connect_error){
			throw new \InvalidKeyException($db->connect_error);
		}
		if(isset($createSchema)){
			$db->query("CREATE SCHEMA `$cred->schema`");
			if($db->error){
				throw new \InvalidKeyException("Schema does not exist and cannot be created");
			}
		}
		return $db;
	}
}
