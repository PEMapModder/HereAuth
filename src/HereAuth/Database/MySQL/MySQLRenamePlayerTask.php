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
use pocketmine\Server;

class MySQLRenamePlayerTask extends AsyncQueryTask{
	/** @type string */
	private $tableName;
	/** @type string */
	private $oldName, $newName;
	/** @type int */
	private $hookId;

	private $success;

	public function __construct(MySQLDatabase $db, $oldName, $newName, $hookId){
		parent::__construct($db->getCredentials());
		$this->tableName = $db->getMainTable();
		$this->oldName = strtolower($oldName);
		$this->newName = strtolower($newName);
		$this->hookId = $hookId;
	}

	public function onRun(){
		$db = $this->getMysqli();
		$result = $db->query("SELECT * FROM `$this->tableName` WHERE name='{$db->escape_string($this->oldName)}'");
		$row = $result->fetch_assoc();
		$result->close();
		if(!is_array($row)){
			$this->success = Database::RENAME_SOURCE_ABSENT;
			return;
		}
		$oldHash = $row["hash"];
		$changes = ["name='" . $db->escape_string($this->newName) . "'"];
		if($oldHash !== "{RENAMED}"){ // if the account is already renamed, we don't need to multi-hash it again because it is already multi-hashed
			$changes[] = "hash='" . $db->escape_string("{RENAMED}") . "'";
			$changes[] = "multihash='" . $db->escape_string(json_encode(["renamed;$this->oldName" => $oldHash])) . "'";
		}
		$result = $db->query("UPDATE `$this->tableName` SET " . implode(",", $changes) . " WHERE name='{$db->escape_string($this->oldName)}'");
		if($result === false){
			if(strpos($db->error, "Duplicate entry ") === 0){
				$this->success = Database::RENAME_TARGET_PRESENT;
				return;
			}
			$this->success = Database::UNKNOWN_ERROR;
			return;
		}
		$this->success = Database::SUCCESS;
	}

	public function onCompletion(Server $server){
		$main = HereAuth::getInstance($server);
		if($main !== null){
			$hook = $main->getFridge()->get($this->hookId);
			if(is_callable($hook)){
				$hook($this->success);
			}
		}
	}
}
