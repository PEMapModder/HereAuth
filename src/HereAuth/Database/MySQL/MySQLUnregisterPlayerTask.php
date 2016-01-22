<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace HereAuth\Database\MySQL;

use HereAuth\HereAuth;
use pocketmine\Server;

class MySQLUnregisterPlayerTask extends AsyncQueryTask{
	/** @type string */
	private $tableName, $name;
	/** @type int */
	private $hookId;

	private $success;

	public function __construct(MySQLDatabase $database, $name, $hookId){
		parent::__construct($database->getCredentials());
		$this->tableName = $database->getMainTable();
		$this->name = $name;
		$this->hookId = $hookId;
	}

	public function onRun(){
		$db = $this->getMysqli();
		$db->query("DELETE FROM `$this->tableName` WHERE name='{$db->escape_string($this->name)}'");
		$this->success = $db->affected_rows > 0;
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
