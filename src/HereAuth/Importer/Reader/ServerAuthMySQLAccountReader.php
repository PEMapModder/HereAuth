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
use HereAuth\Importer\ImporterThread;
use HereAuth\Importer\Writer\AccountWriter;
use HereAuth\User\AccountInfo;
use HereAuth\Utils\FormattedArgumentMap;
use HereAuth\Utils\StringUtils;

class ServerAuthMySQLAccountReader extends AccountReader{
	/** @type MySQLCredentials */
	private $cred;

	public function __construct(HereAuth $main, ImporterThread $thread){
		parent::__construct($main, $thread);
		$this->cred = clone MySQLCredentials::fromConfig($main->getConfig());
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
		$this->setStatus("Connecting");
		try{
			$conn = MySQLDatabase::createMysqliInstance($this->cred);
		}catch(\Exception $e){
			throw new \InvalidArgumentException("Could not connect to $this->cred: $e");
		}
		$prefix = $args->opt("prefix", null);
		$prefix = $args->opt("pfx", $prefix);
		if($prefix === null){
			$this->setStatus("Searching for tables");
			$result = $conn->query("SHOW TABLES LIKE '%serverauth%'");
			if($result === false){
				throw new \InvalidArgumentException("Could not search tables");
			}
			$prefixes = [];
			while(is_array($row = $result->fetch_array(MYSQLI_NUM))){
				if(StringUtils::endsWith($row[0], "serverauth")){
					$prefix = substr($row[0], 0, -10);
					if(isset($prefixes[$prefix])){
						$prefixes[$prefix]++;
					}else{
						$prefixes[$prefix] = 1;
					}
				}elseif(StringUtils::endsWith($row[0], "serverauthdata")){
					$prefix = substr($row[0], 0, -14);
					if(isset($prefixes[$prefix])){
						$prefixes[$prefix]++;
					}else{
						$prefixes[$prefix] = 1;
					}
				}
			}
			$result->close();
			foreach($prefixes as $prefix => $cnt){
				if($cnt === 2){
					$ok = true;
					break;
				}
			}
			if(!isset($ok)){
				throw new \RuntimeException("ServerAuth tables not found in $this->cred");
			}
		}
		$serverAuthTable = $prefix . "serverauth";
		$serverAuthDataTable = $prefix . "serverauthdata";
		$hashMethod = $args->opt("hash", null);
		if($hashMethod === null){
			$this->setStatus("Detecting hash algorithm");
			$result = $conn->query("SELECT password_hash FROM `$serverAuthTable`");
			$row = $result->fetch_assoc();
			if(!is_array($row)){
				throw new \RuntimeException("Corrupted ServerAuth database: serverauth table empty");
			}
			$hashMethod = $row["password_hash"];
			if(!in_array($hashMethod, hash_algos())){
				throw new \RuntimeException("ServerAuth database uses a hash algorithm not supported by PHP " . PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION . "." . PHP_RELEASE_VERSION . ". This may imply a corrupted database.");
			}
		}
		$this->setStatus("Preparing data for transfer");
		$result = $conn->query("SELECT user,password,ip,firstlogin,lastlogin FROM `$serverAuthDataTable`");
		$this->setStatus("Transferring");
		if($result instanceof \mysqli_result){
			$rows = 0;
			while(is_array($row = $result->fetch_assoc())){
				$info = AccountInfo::defaultInstance($row["user"], $this->defaultOpts);
				echo $row["user"] . "\n";
				$info->registerTime = (int) $row["firstlogin"];
				$info->lastLogin = (int) $row["lastlogin"];
				$info->lastIp = $row["ip"];
				$info->passwordHash = "{IMPORTED}";
				$info->multiHash = ["saltless;" . $hashMethod => $row["password"], "nonhash:salt" => strtolower($row["user"])];
				$writer->write($info);
				$this->setProgress((++$rows) / $result->num_rows);
			}
			$result->close();
		}else{
			throw new \InvalidArgumentException("Not a SimpleAuth user database");
		}
	}
}
