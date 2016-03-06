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

use HereAuth\HereAuth;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class JsonSaveDataTask extends AsyncTask{
	/** @type string */
	private $path;
	/** @type string */
	private $contents;

	/** @type bool */
	private $isReg = true;
	/** @type string */
	private $ip;
	/** @type string */
	private $name;
	/** @type int */
	private $time;

	public function __construct($path, $contents, $ip, $name, $time){
		$this->path = $path;
		$this->contents = $contents;
		$this->ip = $ip;
		$this->name = $name;
		$this->time = $time;
	}

	public function onRun(){
		if(is_file($this->path)){
			$old = json_decode(zlib_decode(file_get_contents($this->path)));
			if(is_object($old)){
				$time = $old->registerTime;
				if($time !== -1){
					$this->isReg = false;
				}
			}
		}
		file_put_contents($this->path, zlib_encode($this->contents, ZLIB_ENCODING_DEFLATE));
	}

	public function onCompletion(Server $server){
		if(!$this->isReg or $this->time === -1){
			return;
		}
		$main = HereAuth::getInstance($server);
		if($main === null){
			return;
		}
		$db = $main->getDataBase();
		if(!($db instanceof JsonDatabase)){
			return;
		}
		$stmt = $db->getSQLite3()->prepare("INSERT OR REPLACE INTO reg (ip, name, time) VALUES (:ip, :name, :time)");
		$stmt->bindValue(":ip", $this->ip, SQLITE3_TEXT);
		$stmt->bindValue(":name", strtolower($this->name), SQLITE3_TEXT);
		$stmt->bindValue(":time", $this->time, SQLITE3_INTEGER);
		$stmt->execute();
	}
}
