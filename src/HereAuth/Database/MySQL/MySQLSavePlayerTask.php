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

use HereAuth\User\AccountInfo;

class MySQLSavePlayerTask extends AsyncQueryTask{
	/** @type AccountInfo */
	private $info;
	/** @type string */
	private $tableName;

	public function __construct(MySQLDatabase $db, AccountInfo $info){
		parent::__construct($db->getCredentials());
		$this->info = $info;
		$this->tableName = $db->getMainTable();
	}

	public function onRun(){
		$db = $this->getMysqli();
		$query = $this->info->getDatabaseQuery($this->tableName, new MySQLEscapeInvokable($db));
		$result = $db->query($query);
		if($result === false){
			echo "Error: $db->error\n";
		}
	}
}
