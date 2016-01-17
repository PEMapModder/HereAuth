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
	private $oldPath;private $newPath;
	private $oldName;private $newName;
	public function __construct(JsonDatabase $database, $oldName, $newName){$this->oldPath=$database->
		$this->oldName = $oldName;
		$this->newName = $newName;
	}

	/**
	 * Actions to execute when run
	 *
	 * @return void
	 */
	public function onRun(){
		// TODO: Implement onRun() method.
	}
}
