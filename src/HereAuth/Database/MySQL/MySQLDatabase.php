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
use HereAuth\Importer\Writer\MySQLAccountWriter;
use HereAuth\User\AccountInfo;

class MySQLDatabase implements Database{
	/** @type HereAuth */
	private $main;
	/** @type MySQLCredentials */
	private $crd;

	/** @type string */
	private $mainTable;

	public function __construct(HereAuth $main){
		$this->main = $main;
		$this->crd = MySQLCredentials::fromConfig($main->getConfig());
		$this->mainTable = $main->getConfig()->getNested("Database.MySQL.TablePrefix", "hereauth_") . "accs";
		$main->getLogger()->info("Initializing database...");
		$db = self::createMysqliInstance($this->crd);
		$db->query("CREATE TABLE IF NOT EXISTS `$this->mainTable` (
			name VARCHAR(63) PRIMARY KEY,
			hash BINARY(64),
			register INT UNSIGNED,
			login INT UNSIGNED,
			ip VARCHAR(50),
			secret BINARY(16),
			uuid BINARY(16),
			skin BINARY(64),
			opts VARCHAR(1024),
			multihash VARCHAR(1024)
		)");
		if(isset($db->error) and $db->error){
			throw new \RuntimeException($db->error);
		}
		$db->close();
	}

	public function loadFor($name, $identifier){
		$this->main->getServer()->getScheduler()->scheduleAsyncTask(new MySQLLoadPlayerTask($this, $name, $identifier));
	}

	public function saveData(AccountInfo $info, $overwrite = true){
		$this->main->getServer()->getScheduler()->scheduleAsyncTask(new MySQLSavePlayerTask($this, $info, $overwrite));
	}

	public function renameAccount($oldName, $newName, callable $hook){
		$hookId = $this->main->getFridge()->store($hook);
		$this->main->getServer()->getScheduler()->scheduleAsyncTask(new MySQLRenamePlayerTask($this, $oldName, $newName, $hookId));
	}

	public function unregisterAccount($name, callable $hook){
		$name = strtolower($name);
		$hookId = $this->main->getFridge()->store($hook);
		$this->main->getServer()->getScheduler()->scheduleAsyncTask(new MySQLUnregisterPlayerTask($this, $name, $hookId));
	}

	public function passesLimit($ip, $limit, $time, $identifier){
	}

	public function close(){
	}

	/**
	 * @param MySQLCredentials $crd
	 * @param HereAuth         $main
	 *
	 * @return \mysqli
	 */
	public static function createMysqliInstance(MySQLCredentials $crd, HereAuth $main = null){
		/** @noinspection PhpUsageOfSilenceOperatorInspection */
		$db = @new \mysqli($crd->host, $crd->username, $crd->password, $crd->schema, $crd->port, $crd->socket);
		if($db->connect_error === "Unknown database '$crd->schema'"){
			/** @noinspection PhpUsageOfSilenceOperatorInspection */
			$db = @new \mysqli($crd->host, $crd->username, $crd->password, "", $crd->port, $crd->socket);
			$createSchema = true;
		}
		if($db->connect_error){
			throw new \InvalidKeyException($db->connect_error);
		}
		if(isset($createSchema)){
			if($main !== null){
				$main->getLogger()->notice("Creating nonexistent MySQL schema `$crd->schema`...");
			}
			$db->query("CREATE SCHEMA `$crd->schema`");
			if(isset($db->error)){
				throw new \InvalidKeyException("Schema does not exist and cannot be created");
			}
		}
		return $db;
	}

	/**
	 * @return MySQLCredentials
	 */
	public function getCredentials(){
		return $this->crd;
	}

	/**
	 * @return string
	 */
	public function getMainTable(){
		return $this->mainTable;
	}

	/**
	 * @return HereAuth
	 */
	public function getMain(){
		return $this->main;
	}

	public function getAccountWriter(&$writerArgs) : string{
		$writerArgs = [$this->crd, $this->mainTable];
		return MySQLAccountWriter::class;
	}
}
