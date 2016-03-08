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

use HereAuth\User\AccountInfo;
use SQLite3;

class JSONAccountWriter extends AccountWriter{
	/** @type bool */
	private $overwrite;
	/** @type string */
	private $path;
	/** @type bool */
	private $indexEnabled;
	/** @type SQLite3 */
	private $sql;

	public function __construct(bool $overwrite, string $path, bool $indexEnabled, string $currentVersion){
		$this->overwrite = $overwrite;
		if(!is_dir($path)){
			mkdir($path, 0777, true);
		}
		if(!is_dir($path)){
			throw new \RuntimeException("Could not create data directory at $path");
		}
		$path = realpath($path) . "/";
		if(is_file($hadb = $path . ".hadb")){
			$data = json_decode(file_get_contents($hadb), true);
		}else{
			$data = [
				"#" => "This is a HereAuth JSON-based account database.",
				"created" => time(),
				"lastClosed" => time(),
			];
		}
		$data["lastOpened"] = time();
		$data["version"] = $currentVersion;
		file_put_contents($hadb, json_encode($data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_SLASHES));

		$this->path = $path;
		$this->indexEnabled = $indexEnabled;

		$this->sql = new SQLite3($path . "reg.db");
		$this->sql->exec("CREATE TABLE IF NOT EXISTS reg (ip TEXT, name TEXT PRIMARY KEY, time INTEGER)");
	}

	public function write(AccountInfo $info){
		$path = $this->getPath($info->name);
		if($isOverwrite = is_file($path)){
			if(!$this->overwrite){
				return;
			}
		}
		file_put_contents($path, zlib_encode($info->serialize(), ZLIB_ENCODING_DEFLATE));

		if(!$isOverwrite and $info->registerTime !== -1){
			$stmt = $this->sql->prepare("INSERT OR REPLACE INTO reg (ip, name, time) VALUES (:ip, :name, :time)");
			$stmt->bindValue(":ip", $info->lastIp, SQLITE3_TEXT);
			$stmt->bindValue(":name", strtolower($info->name), SQLITE3_TEXT);
			$stmt->bindValue(":time", $info->registerTime, SQLITE3_INTEGER);
			$stmt->execute();
		}
	}

	public function getPath($name){
		return $this->path . ($this->indexEnabled ? ($name{0} . "/") : "") . strtolower($name) . ".json";
	}
}
