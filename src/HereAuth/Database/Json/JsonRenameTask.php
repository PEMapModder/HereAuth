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

use pocketmine\scheduler\AsyncTask;

class JsonRenameTask extends AsyncTask{
	private $oldPath;
	private $newPath;
	private $oldName;
	private $newName;

	public function __construct(JsonDatabase $database, $oldName, $newName){
		$this->oldPath = $database->getPath($this->oldName = strtolower($oldName));
		$this->newPath = $database->getPath($this->newName = strtolower($newName));
	}

	public function onRun(){
		if(!is_file($this->oldPath)){
			$this->setResult("File didn't exist", false);
			return;
		}
		if(!is_dir($dir = dirname($this->newPath))){
			mkdir($dir);
		}
		$data = json_decode(file_get_contents($this->oldPath));
		$data->multiHash = ["renamed;$this->oldName" => $data->passwordHash];
		$data->passwordHash = "{RENAMED}";
		file_put_contents($this->newPath, json_encode($data));
	}
}
