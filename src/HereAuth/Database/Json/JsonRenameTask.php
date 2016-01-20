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
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class JsonRenameTask extends AsyncTask{
	private $oldPath;
	private $newPath;
	private $oldName;
	private $newName;

	private $hook;
	private $success;

	public function __construct(JsonDatabase $database, $oldName, $newName, $hookId){
		$this->oldPath = $database->getPath($this->oldName = strtolower($oldName));
		$this->newPath = $database->getPath($this->newName = strtolower($newName));
		$this->hook = $hookId;
	}

	public function onRun(){
		if(is_file($this->newPath)){
			$this->success = Database::RENAME_TARGET_PRESENT;
			return;
		}
		if(!is_file($this->oldPath)){
			$this->setResult("File didn't exist", false);
			$this->success = Database::RENAME_SOURCE_ABSENT;
			return;
		}
		if(!is_dir($dir = dirname($this->newPath))){
			mkdir($dir);
		}
		$data = json_decode(zlib_decode(file_get_contents($this->oldPath)));
		$data->multiHash = ["renamed;$this->oldName" => $data->passwordHash];
		$data->passwordHash = "{RENAMED}";
		unlink($this->oldPath);
		file_put_contents($this->newPath, zlib_encode(json_encode($data), ZLIB_ENCODING_DEFLATE));
		$this->success = Database::SUCCESS;
	}

	public function onCompletion(Server $server){
		$main = HereAuth::getInstance($server);
		if($main === null){
			return;
		}
		$hook = $main->getFridge()->get($this->hook);
		$hook($this->success);
	}
}
