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

namespace HereAuth\Database\Json;

use HereAuth\Database\Database;
use HereAuth\HereAuth;
use HereAuth\User\AccountInfo;
use pocketmine\Player;
use pocketmine\scheduler\FileWriteTask;
use SQLite3;

class JsonDatabase implements Database{
	/** @type HereAuth */
	private $main;
	/** @type string */
	private $path;
	/** @type bool */
	private $indexEnabled;

	/** @type SQLite3 */
	private $sql;

	public function __construct(HereAuth $main){
		$this->main = $main;
		$this->path = $main->getConfig()->getNested("Database.JSON.DataFolder", "accounts");
		if($this->path{0} !== "/"){
			$this->path = $main->getDataFolder() . $this->path;
		}
		if(!is_dir($this->path)){
			mkdir($this->path, 0777, true);
		}
		if(!is_dir($this->path)){
			throw new \RuntimeException("Could not create data directory at $this->path");
		}
		$this->path = realpath($this->path) . "/";
		$this->indexEnabled = $main->getConfig()->getNested("Database.JSON.EnableLeadingIndex", false);
		if(is_file($hadb = $this->path . ".hadb")){
			$data = json_decode(file_get_contents($hadb), true);
		}else{
			$data = [
				"#" => "This is a HereAuth JSON-based account database.",
				"created" => time(),
				"lastClosed" => time(),
			];
		}
		$data["lastOpened"] = time();
		$data["version"] = $this->main->getDescription()->getVersion();
		file_put_contents($hadb, json_encode($data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_SLASHES));

		$this->sql = new SQLite3($this->path . "reg.db");
		$this->sql->exec("CREATE TABLE IF NOT EXISTS reg (ip TEXT, name TEXT, time INTEGER)");
	}

	public function loadFor($name, $identifier){
		$this->main->getServer()->getScheduler()->scheduleAsyncTask(new JsonLoadFileTask($this->getPath($name), $identifier));
	}

	public function saveData($name, AccountInfo $info){
		$path = $this->getPath($name);
		$existed = file_exists($path);
		$this->main->getServer()->getScheduler()->scheduleAsyncTask(new FileWriteTask($path, $info->serialize()));
		if(!$existed){
			$stmt = $this->sql->prepare("INSERT INTO reg (ip, name, time) VALUES (:ip, :name, :time)");
			$stmt->bindValue(":ip", $info->lastIp, SQLITE3_TEXT);
			$stmt->bindValue(":name", strtolower($info->name), SQLITE3_TEXT);
			$stmt->bindValue(":time", $info->registerTime, SQLITE3_INTEGER);
			$stmt->execute();
		}
	}

	public function passesLimit($ip, $limit, $time, $identifier){
		$stmt = $this->sql->prepare("SELECT COUNT(*) AS cnt FROM reg WHERE ip=:ip AND time >= :time");
		$stmt->bindValue(":ip", $ip, SQLITE3_TEXT);
		$stmt->bindValue(":time", $time, SQLITE3_INTEGER);
		$cnt = $stmt->execute()->fetchArray(SQLITE3_ASSOC)["cnt"];
		if($cnt >= $limit){
			$player = $this->main->getPlayerById($identifier);
			if($player instanceof Player){
				$player->kick("You created too many accounts!", false);
			}
		}
	}

	protected function getPath($name){
		return $this->path . ($this->indexEnabled ? ($name{0} . "/") : "") . strtolower($name) . ".json";
	}

	public function close(){
		$data = json_decode(file_get_contents($this->path));
		$data->lastClosed = time();
		file_put_contents($this->path, json_encode($data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_SLASHES));
		$this->sql->close();
	}
}
