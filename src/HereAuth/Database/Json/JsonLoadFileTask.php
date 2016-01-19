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
use HereAuth\User\AccountInfo;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class JsonLoadFileTask extends AsyncTask{
	/** @type string */
	private $path;
	/** @type int */
	private $identifier;
	/** @type string|null */
	private $output;

	public function __construct($path, $identifier){
		$this->path = $path;
		$this->identifier = $identifier;
	}

	public function onRun(){
		$this->output = is_file($this->path) ? zlib_decode(file_get_contents($this->path)) : null;
	}

	public function onCompletion(Server $server){
		$main = HereAuth::getInstance($server);
		if($main !== null){
			if($this->output !== null){
				$output = new AccountInfo;
				$output->unserialize($this->output);
			}else{
				$output = null;
			}
			$main->onUserStart($this->identifier, $output);
		}
	}
}
