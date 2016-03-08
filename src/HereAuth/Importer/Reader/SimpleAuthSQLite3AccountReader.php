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
use HereAuth\Utils\FormattedArgumentMap;

class SimpleAuthSQLite3AccountReader extends AccountReader{
	public function read($params, AccountWriter $writer){
		$args = new FormattedArgumentMap($params);
		$folder = $args->opt("i", "plugins/SimpleAuth");
		if(!is_dir($folder)){
			throw new \InvalidArgumentException("Input database $folder not found or is not a directory");
		}
		$path = rtrim($folder, "/\\") . "/players.db";
		if(!is_file($path)){
			return;
		}
		$this->setStatus("Opening database");
		$db = new \SQLite3($path);
		$result = $db->query("SELECT COUNT(*) AS cnt FROM players");
		$total = $result->fetchArray(SQLITE3_ASSOC)["cnt"];
		$result->finalize();
		$this->setStatus("Preparing data");
		$result = $db->query("SELECT name,registerdate,logindate,lastip,hash FROM players");
		$i = 0;
		while(is_array($row = $result->fetchArray(SQLITE3_ASSOC))){
			$i++;
			$info = AccountInfo::defaultInstance($row["name"], $this->defaultOpts);
			$info->lastIp = $row["lastip"];
			$info->registerTime = $row["registerdate"];
			$info->lastLogin = $row["logindate"];
			$info->passwordHash = hex2bin($row["hash"]);
			$writer->write($info);
			$this->setProgress($i / $total);
		}
		$db->close();
	}
}
