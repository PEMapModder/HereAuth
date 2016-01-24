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

namespace HereAuth\Importer\Reader;

use HereAuth\Importer\Writer\AccountWriter;
use HereAuth\User\AccountInfo;
use HereAuth\User\AccountOpts;

class SimpleAuthSQLite3AccountReader extends AccountReader{
	/** @type AccountOpts */
	private $defaultOpts;

	public function read($args, AccountWriter $writer){
		$path = "plugins/SimpleAuth/players.db"; // TODO customize
		if(!is_file($path)){
			return;
		}
		$db = new \SQLite3($path);
		$result = $db->query("SELECT COUNT(*) AS cnt FROM players");
		$total = $result->fetchArray(SQLITE3_ASSOC)["cnt"];
		$result->finalize();
		$result = $db->query("SELECT name,registerdate,logindate,lastip,hash FROM players");
		$i = 0;
		while(is_array($row = $result->fetchArray(SQLITE3_ASSOC))){
			$i++;
			$info = AccountInfo::defaultInstance($row["name"], $this->defaultOpts);
			$info->lastIp = $row["lastip"];
			$info->registerTime = $row["registerdate"];
			$info->lastLogin = $row["logindate"];
			$info->passwordHash = $row["hash"];
			$writer->write($info);
			$this->setProgress($i / $total);
		}
		$db->close();
	}
}
