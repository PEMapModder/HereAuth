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

use HereAuth\HereAuth;
use HereAuth\User\AccountInfo;
use pocketmine\Server;

class MySQLLoadPlayerTask extends AsyncQueryTask{
	/** @type string */
	private $tableName;
	/** @type string */
	private $name;
	/** @type string */
	private $identifier;

	public function __construct(MySQLDatabase $db, $name, $identifier){
		parent::__construct($db->getCredentials());
		$this->tableName = $db->getMainTable();
		$this->name = $name;
		$this->identifier = $identifier;
	}

	public function onRun(){
		$db = $this->getMysqli();
		$result = $db->query("SELECT * FROM `$this->tableName` WHERE name='{$db->escape_string($this->name)}'");
		$row = $result->fetch_assoc();
		$result->close();
		if(!is_array($row)){
			$this->setResult(false, false);
			return;
		}
		$row["hash"] = rtrim($row["hash"], "\0");
		$this->setResult($row);
	}

	public function onCompletion(Server $server){
		$main = HereAuth::getInstance($server);
		if($main === null){
			return;
		}
		$result = $this->getResult();
		if(is_array($result)){
			$info = AccountInfo::fromDatabaseRow($result);
		}else{
			$info = null;
		}
		$main->onUserStart($this->identifier, $info);
	}
}
