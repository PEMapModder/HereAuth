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

class JsonUnregisterTask extends AsyncTask{
	/** @type string */
	private $path;

	/** @type int */
	private $hook;

	private $success = false;

	public function __construct(JsonDatabase $db, $name, $id){
		$this->path = $db->getPath($name);
		$this->hook = $id;
	}

	public function onRun(){
		if(is_file($this->path)){
			unlink($this->path);
			$this->success = true;
		}
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
