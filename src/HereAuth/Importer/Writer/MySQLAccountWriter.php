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

namespace HereAuth\Importer\Writer;

use HereAuth\Database\MySQL\MySQLCredentials;
use HereAuth\Database\MySQL\MySQLDatabase;
use HereAuth\Database\MySQL\MySQLEscapeInvokable;
use HereAuth\User\AccountInfo;

class MySQLAccountWriter extends AccountWriter{
	/** @type \mysqli */
	private $mysqli;
	/** @type string */
	private $tableName;
	/** @type bool */
	private $overwrite;

	public function __construct(bool $overwrite, MySQLCredentials $cred, string $tableName){
		$this->overwrite = $overwrite;
		$this->mysqli = MySQLDatabase::createMysqliInstance($cred);
		$this->tableName = $tableName;
	}

	public function write(AccountInfo $info){
		$this->mysqli->query($info->getDatabaseQuery($this->tableName, new MySQLEscapeInvokable($this->mysqli), $this->overwrite));
	}
}
