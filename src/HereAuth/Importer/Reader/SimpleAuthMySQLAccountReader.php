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

use HereAuth\Database\MySQL\MySQLCredentials;
use HereAuth\Database\MySQL\MySQLDatabase;
use HereAuth\HereAuth;
use HereAuth\Importer\Writer\AccountWriter;
use HereAuth\User\AccountInfo;
use HereAuth\Utils\FormattedArgumentMap;

class SimpleAuthMySQLAccountReader extends AccountReader{
	/** @type MySQLCredentials */
	private $cred;

	public function __construct(HereAuth $main){
		parent::__construct($main);
		$this->cred = MySQLCredentials::fromConfig($main->getConfig());
	}

	public function read($params, AccountWriter $writer){
		$args = new FormattedArgumentMap($params);
		$this->cred->host = $args->opt("h", $this->cred->host);
		$this->cred->username = $args->opt("u", $this->cred->username);
		$this->cred->password = $args->opt("p", $this->cred->password);
		$this->cred->schema = $args->opt("d", $this->cred->schema);
		$this->cred->schema = $args->opt("s", $this->cred->schema);
		$this->cred->port = (int) $args->opt("port", $this->cred->port);
		$this->cred->socket = $args->opt("socket", $this->cred->socket);
		$this->cred->socket = $args->opt("sk", $this->cred->socket);
		$conn = MySQLDatabase::createMysqliInstance($this->cred);
		if(isset($conn->connect_error)){
			throw new \InvalidArgumentException("Could not connect to $this->cred");
		}
		$result = $conn->query("SELECT name, registerdate, logindate, lastip, hash FROM simpleauth_players");
		if($result instanceof \mysqli_result){
			while(is_array($row = $result->fetch_assoc())){
				$info = AccountInfo::defaultInstance($result["name"], $this->defaultOpts);
				$info->registerTime = (int) $result["registerdate"];
				$info->lastLogin = (int) $result["logindate"];
				$info->lastIp = $result["lastip"];
				$info->passwordHash = hex2bin($result["lastip"]);
				$writer->write($info);
			}
			$result->close();
		}else{
			throw new \InvalidArgumentException("Not a SimpleAuth user database");
		}
	}
}
