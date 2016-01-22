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

class MySQLPassLimitCheckTask extends AsyncQueryTask{
	/** @type string */
	private $tableName;
	/** @type string */
	private $ip;
	/** @type int */
	private $limit, $since;
	/** @type int */
	private $identifier;
	/** @type bool */
	private $passed;

	public function __construct(MySQLDatabase $database, $ip, $limit, $time, $identifier){
		parent::__construct($database->getCredentials());
		$this->tableName = $database->getMainTable();
		$this->ip = $ip;
		$this->limit = $limit;
		$this->since = time() - $time;
		$this->identifier = $identifier;
	}

	public function onRun(){
		$db = $this->getMysqli();
		$result = $db->query("SELECT COUNT(*) AS cnt FROM `$this->tableName` WHERE ip='{$db->escape_string($this->ip)}' AND register >= $this->since");
		$row = $result->fetch_assoc();
		$result->close();
		$cnt = (int) $row["cnt"];
		$this->passed = $cnt < $this->limit;
	}

	public function onCompletion(Server $server){
		if(!$this->passed){
			$main = HereAuth::getInstance($server);
			if($main !== null){
				$player = $main->getPlayerById($this->identifier);
				$player->kick("You created too many accounts!", false);
			}
		}
	}
}
